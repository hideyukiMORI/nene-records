# NeNe Records

API-first flexible entity platform built on [NENE2](https://github.com/hideyukiMORI/NENE2).

NeNe Records lets you define entity types and typed fields from the admin frontend, persist them through a thin JSON API, and expose the same operations to humans, SPAs, and AI agents via OpenAPI and MCP.

## Goals

- **Lighter than WordPress**: no monolithic PHP runtime, no untyped post meta chaos
- **Readable**: explicit schema registry, typed value tables, OpenAPI contracts
- **Secure**: auth and validation at the API boundary; MCP tools never touch the database directly
- **Replaceable layers**: swap the API runtime, admin UI, or consumer views independently

## Architecture

```
Admin frontend (React)  ──┐
Consumer views          ──┼──→  NeNe Records API (NENE2)  ──→  Database
AI clients (MCP)        ──┘
```

## Status

Early planning. See [Issues](https://github.com/hideyukiMORI/nene-records/issues) for roadmap.

## Related

- [NENE2](https://github.com/hideyukiMORI/NENE2) — PHP micro-framework (HTTP runtime, OpenAPI, MCP)
- [NENE2-FT](https://github.com/hideyukiMORI/NENE2-FT) — field trials and reference implementations

## License

MIT
