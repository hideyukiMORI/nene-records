# OpenAPI Contract Self-Review

Use when changing `docs/openapi/openapi.yaml` or API response shapes.

Source policies:

- `docs/development/coding-standards.md`
- NENE2: `docs/integrations/openapi.md`

## Checklist

- [ ] Every shipped JSON endpoint has an OpenAPI path with `operationId`.
- [ ] Success and error responses reference shared Problem Details schemas where applicable.
- [ ] Request bodies and query parameters match handler DTO validation rules.
- [ ] Examples are realistic (`ok` examples for success paths).
- [ ] `composer openapi` passes after runtime scaffold exists.
- [ ] MCP tool entries in `docs/mcp/tools.json` align with OpenAPI when tools are added.
- [ ] Public text in OpenAPI descriptions is English.
