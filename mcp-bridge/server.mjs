// Thin remote MCP server (Streamable HTTP) that bridges docs/mcp/tools.json to
// the NeNe Records HTTP API, so Claude.ai can register it as a custom connector.
// VERIFICATION USE: run locally + expose via cloudflared while your PC is on.
// See docs/integrations/mcp-connector.md.

import { readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { randomUUID } from 'node:crypto'
import express from 'express'
import { Server } from '@modelcontextprotocol/sdk/server/index.js'
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js'
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  isInitializeRequest,
} from '@modelcontextprotocol/sdk/types.js'

// ── Config (env) ────────────────────────────────────────────────────────────
const API_BASE = (process.env.NENE_API_BASE ?? 'http://localhost:18082').replace(/\/$/, '')
const API_TOKEN = process.env.NENE_API_TOKEN ?? ''
const PORT = Number(process.env.MCP_PORT ?? 8765)
const MCP_PATH = process.env.MCP_PATH ?? '/mcp'
const READONLY = process.env.MCP_READONLY !== '0' // default: read tools only
const SCOPE = process.env.MCP_TOOLS ?? 'themes' // 'themes' | 'all'
const BRIDGE_SECRET = process.env.MCP_BRIDGE_SECRET ?? '' // optional ?key= guard

if (API_TOKEN === '') {
  console.error('NENE_API_TOKEN is required (admin endpoints need auth). See the docs.')
  process.exit(1)
}

// ── Load + filter the tool catalogue ────────────────────────────────────────
const here = dirname(fileURLToPath(import.meta.url))
const catalogue = JSON.parse(readFileSync(resolve(here, '../docs/mcp/tools.json'), 'utf8'))

/** Normalise an inputSchema: tools.json serialises "no properties" as [] (PHP). */
function normaliseSchema(schema) {
  const s = { ...(schema ?? {}) }
  s.type ??= 'object'
  if (Array.isArray(s.properties)) s.properties = {}
  s.properties ??= {}
  return s
}

const tools = catalogue.tools
  .filter((t) => (SCOPE === 'all' ? true : /theme/i.test(t.name)))
  .filter((t) => (READONLY ? t.safety === 'read' : true))
  .map((t) => ({
    name: t.name,
    description: t.description,
    inputSchema: normaliseSchema(t.inputSchema),
    _source: t.source, // { method, path }
  }))

const byName = new Map(tools.map((t) => [t.name, t]))

console.error(
  `[mcp-bridge] ${tools.length} tool(s) exposed (scope=${SCOPE}, readonly=${READONLY}): ` +
    tools.map((t) => t.name).join(', '),
)

// ── Proxy a tool call to the NeNe Records HTTP API ──────────────────────────
async function callTool(name, args) {
  const tool = byName.get(name)
  if (!tool) {
    return { content: [{ type: 'text', text: `Unknown tool: ${name}` }], isError: true }
  }

  const { method, path } = tool._source
  const rest = { ...args }

  // Substitute {param} path segments, consuming them from the args.
  const filledPath = path.replace(/\{(\w+)\}/g, (_, key) => {
    const value = rest[key]
    delete rest[key]
    return encodeURIComponent(String(value ?? ''))
  })

  let url = API_BASE + filledPath
  const init = {
    method,
    headers: {
      Authorization: `Bearer ${API_TOKEN}`,
      Accept: 'application/json',
    },
  }

  if (method === 'GET' || method === 'DELETE') {
    const qs = new URLSearchParams()
    for (const [k, v] of Object.entries(rest)) qs.append(k, String(v))
    if ([...qs].length > 0) url += `?${qs.toString()}`
  } else {
    init.headers['Content-Type'] = 'application/json'
    init.body = JSON.stringify(rest)
  }

  try {
    const res = await fetch(url, init)
    const text = await res.text()
    return {
      content: [{ type: 'text', text: text === '' ? `(${res.status})` : text }],
      isError: !res.ok,
    }
  } catch (err) {
    return { content: [{ type: 'text', text: `Bridge request failed: ${String(err)}` }], isError: true }
  }
}

function buildServer() {
  const server = new Server(
    { name: 'nene-records-themes', version: '0.1.0' },
    { capabilities: { tools: {} } },
  )
  server.setRequestHandler(ListToolsRequestSchema, async () => ({
    tools: tools.map(({ name, description, inputSchema }) => ({ name, description, inputSchema })),
  }))
  server.setRequestHandler(CallToolRequestSchema, async (req) =>
    callTool(req.params.name, req.params.arguments ?? {}),
  )
  return server
}

// ── HTTP (Streamable HTTP transport, stateful sessions) ─────────────────────
const app = express()
app.use(express.json({ limit: '4mb' }))

app.get('/health', (_req, res) => res.json({ ok: true, tools: tools.length }))

/** Optional obscurity guard: if MCP_BRIDGE_SECRET is set, require ?key=<secret>. */
function guard(req, res) {
  if (BRIDGE_SECRET !== '' && req.query.key !== BRIDGE_SECRET) {
    res.status(401).send('Unauthorized')
    return false
  }
  return true
}

/** sessionId -> transport */
const transports = {}

app.post(MCP_PATH, async (req, res) => {
  if (!guard(req, res)) return

  const sessionId = req.headers['mcp-session-id']
  let transport = sessionId ? transports[sessionId] : undefined

  if (transport === undefined && isInitializeRequest(req.body)) {
    transport = new StreamableHTTPServerTransport({
      sessionIdGenerator: () => randomUUID(),
      onsessioninitialized: (sid) => {
        transports[sid] = transport
      },
    })
    transport.onclose = () => {
      if (transport.sessionId) delete transports[transport.sessionId]
    }
    await buildServer().connect(transport)
  } else if (transport === undefined) {
    res.status(400).json({
      jsonrpc: '2.0',
      error: { code: -32000, message: 'Bad Request: no valid session ID' },
      id: null,
    })
    return
  }

  await transport.handleRequest(req, res, req.body)
})

async function handleSessionRequest(req, res) {
  if (!guard(req, res)) return
  const sessionId = req.headers['mcp-session-id']
  const transport = sessionId ? transports[sessionId] : undefined
  if (transport === undefined) {
    res.status(400).send('Invalid or missing session ID')
    return
  }
  await transport.handleRequest(req, res)
}

app.get(MCP_PATH, handleSessionRequest)
app.delete(MCP_PATH, handleSessionRequest)

app.listen(PORT, () => {
  console.error(`[mcp-bridge] listening on http://localhost:${PORT}${MCP_PATH} → ${API_BASE}`)
})
