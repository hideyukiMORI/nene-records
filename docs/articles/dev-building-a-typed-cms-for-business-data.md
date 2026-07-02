---
title: Building a typed CMS for business data with PHP, OpenAPI, and MCP
published: true
description: A NeNe Records deep dive: why I am building a typed, API-first CMS for business data instead of a WordPress-compatible clone.
tags: php, cms, api, opensource
---

In my previous article, I introduced the **NeNe** series: a family of small, self-hosted business tools for teams operating in Japan.

Previous article:

https://dev.to/hideyukimori/i-am-building-self-hosted-business-tools-for-small-teams-in-japan-4i26

This article is a deeper look at one of those tools:

**NeNe Records**.

Repository:

https://github.com/hideyukiMORI/nene-records

NeNe Records is an API-first typed CMS and flexible entity platform built on **NENE2**, my small PHP framework for AI-readable business APIs.

It is not trying to be a WordPress clone.

It is not trying to run WordPress plugins or themes.

The goal is different:

**manage business data and public content through typed schemas, documented APIs, and clear AI tool boundaries.**

## Why not just use WordPress?

WordPress is useful.

It has proven that a generic content model can support blogs, pages, shops, media, and many small business workflows.

But when I think about business data, a few problems keep coming back:

- untyped metadata
- plugin-specific data shapes
- hooks and filters that can change behavior from many places
- API contracts that depend heavily on plugins
- AI integrations that may not know where the real application boundary is

The `postmeta` model is flexible, but it often becomes stringly typed storage.

That is fine for many websites.

But for business data, I usually want the API to know more:

- this field is text
- this field is an enum
- this field is an image
- this field is a relation
- this field is required
- this record belongs to this organization
- this operation requires this role

That is the space where NeNe Records lives.

## Typed records instead of metadata chaos

The core model is simple:

```text
Entity Type
  -> Field Definition
  -> Record
```

An **Entity Type** defines what kind of data exists.

Examples:

- posts
- pages
- products
- events
- internal documents

A **Field Definition** describes the shape of a field:

- text
- markdown
- html
- blocks
- int
- enum
- bool
- datetime
- image
- file
- relation

A **Record** is the actual data.

The important part is that the schema is not only a frontend concern.

The API validates writes against the registered field definitions.

The admin UI can provide a flexible editing experience, but the API remains the boundary that enforces the data shape.

That is the main difference I care about:

**flexible editing, typed persistence.**

## Current status

NeNe Records is already more than a schema experiment.

It has a React admin UI, typed entity definitions, record management, media handling, public pages, OpenAPI documentation, and an OpenAPI-derived MCP tool catalog.

It also supports multi-tenant organization scope, with ongoing work around WordPress migration and production deployment.

It is still a work in progress, but the core shape is already running.

## The architecture

NeNe Records follows the same general shape as the rest of the NeNe series:

```text
React Admin UI
Public pages
AI / MCP clients
        |
        v
Documented HTTP API
        |
        v
Use cases
        |
        v
Repositories
        |
        v
Database
```

The backend is PHP 8.4 on NENE2.

The admin UI is React and TypeScript.

The API contract is OpenAPI 3.1.

MCP tools are derived from the documented API surface.

The important rule is:

**MCP tools should call the application API, not the database.**

That is the same rule I wrote about in my previous article:

https://dev.to/hideyukimori/mcp-should-not-mean-letting-ai-touch-your-database-57p1

For a CMS-like product, this matters a lot.

Content and business data are not just rows.

They have:

- validation rules
- permissions
- organization scope
- publication state
- redirects
- SEO metadata
- media references
- audit and migration concerns

Those rules belong in the application boundary.

## API-first admin

The admin screen is not the source of truth.

It is a client.

It edits entity types, fields, records, media, users, and settings through the same API shape that other clients can understand.

This makes the system easier to reason about.

If a future tool needs to create a record, it should not need a hidden internal shortcut.

It should call the same API that the admin UI calls.

That is also why OpenAPI matters.

The API contract is not only documentation for humans.

It is also a map for:

- generated clients
- contract tests
- MCP tool catalogs
- future automation

## Public pages without a separate Node SSR server

NeNe Records is not only a headless CMS.

It also supports public pages.

The direction is a single-origin PHP application that can return crawlable HTML for public content, with a SPA shell on top.

That includes work around:

- real permalinks
- canonical URLs
- OGP
- Twitter cards
- JSON-LD
- breadcrumbs
- sitemap.xml
- robots.txt
- redirect maps

I do not want every small business deployment to require a separate Node SSR service just to make public pages crawlable.

For this project, the PHP application should be able to serve useful public HTML directly.

## WordPress migration, not WordPress compatibility

NeNe Records is not WordPress-compatible.

That is intentional.

But migration still matters.

Many real teams already have content in WordPress.

So NeNe Records has been growing a WXR import path:

- posts and pages
- tags
- media attachments
- body image URL replacement
- 301 redirect maps
- SEO metadata from common plugins
- import plan preview

The goal is not:

**run WordPress themes and plugins.**

The goal is:

**move content, URLs, media, and SEO intent into a typed API-first platform.**

That distinction is important.

Compatibility with the WordPress runtime would pull the product back toward the same plugin/theme coupling I am trying to avoid.

Migration is the useful part.

Runtime compatibility is not the goal.

## Real-data testing with Aozora Bunko

One thing I have been doing recently is testing NeNe Records with real public-domain text data.

For that, I prepared an importer using **Aozora Bunko** text.

In a test tenant, I imported works by Natsume Soseki as chapter-based records.

The goal is not just to prove that records can be inserted.

The goal is to see whether the system still feels coherent when the data is not a tiny demo.

Things I want to observe:

- list performance with many records
- permalink tree behavior
- chapter navigation
- public page rendering
- sitemap behavior
- search and directory browsing
- admin UI usability with larger content sets

The public test tenant is here:

https://aozora.nene-records.com/

In a browser, it is intended to show the public shell.

If you call it with `curl` and the default `Accept: */*`, you may see an API-style JSON response instead. Normal browser access should resolve as HTML.

This kind of real-data testing is valuable because CMS demos can be misleading.

A CMS with five records can look finished.

A CMS with hundreds or thousands of records starts to reveal where the model, navigation, and public presentation are weak.

## Why this is business software, not only a CMS experiment

I call NeNe Records a typed CMS, but the broader idea is a flexible entity platform.

The same primitives can describe more than blog posts:

- product catalogs
- internal knowledge records
- simple CRM data
- public directories
- structured landing pages
- operational records

The difference from an application framework is that Records keeps the schema editable from the product surface.

The difference from a traditional CMS is that the API and field definitions are not treated as incidental plugin behavior.

They are the center.

## What is still unfinished

I do not want to overstate the maturity.

NeNe Records is still being refined.

Important areas are still evolving:

- settings UI
- finer-grained permissions
- two-factor authentication
- production deployment paths
- hosted / managed options
- more public-site polish
- large-data soak testing

The project is open source and usable for experimentation, but I do not want to present it as a finished CMS replacement.

The honest position is:

**the shape is working, and the product is being hardened through real implementation.**

## What I learned while building it

The biggest lesson is that "simple CMS" is not actually simple.

Once you care about typed data, SEO, public pages, migration, multi-tenancy, media, admin UX, and AI boundaries, the system becomes a real product very quickly.

That is why the architecture needs to stay boring.

The boring parts are the parts I want to trust:

- HTTP APIs
- OpenAPI contracts
- use cases
- repositories
- typed field definitions
- explicit permissions
- MCP behind the API boundary

The product can become flexible only if the boundaries stay strict.

## Links

- NeNe Records: https://github.com/hideyukiMORI/nene-records
- NENE2: https://github.com/hideyukiMORI/NENE2
- Aozora test tenant: https://aozora.nene-records.com/
- Previous NeNe series overview: https://dev.to/hideyukimori/i-am-building-self-hosted-business-tools-for-small-teams-in-japan-4i26
- MCP boundary article: https://dev.to/hideyukimori/mcp-should-not-mean-letting-ai-touch-your-database-57p1

NeNe Records is still a work in progress.

But the direction is clear:

**typed business data, public pages, OpenAPI contracts, and AI tools that stay behind the application boundary.**
