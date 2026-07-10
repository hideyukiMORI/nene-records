import { afterEach, describe, expect, it, vi } from 'vitest'
import { cleanup, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { EntityStatusPanel } from './EntityStatusPanel'
import type { EntityStatusPanelState } from '../hooks/useEntityStatusPanel'
import { toEntityId, type Entity } from '@/entities/entity'
import { renderWithProviders } from '@tests/render/render-with-providers'

afterEach(cleanup)

function makeEntity(overrides: Partial<Entity> = {}): Entity {
  return {
    id: toEntityId(7),
    entityTypeId: 1,
    slug: 'hello',
    permalink: null,
    layout: null,
    showComments: null,
    showRelated: null,
    status: 'draft',
    publishedAt: null,
    scheduledAt: null,
    isDeleted: false,
    deletedAt: null,
    metaTitle: null,
    metaDescription: null,
    menuOrder: 0,
    createdAt: null,
    updatedAt: null,
    ...overrides,
  }
}

function makeState(overrides: Partial<EntityStatusPanelState> = {}): EntityStatusPanelState {
  return {
    entity: makeEntity(),
    entityTypeSlug: 'posts',
    slugInput: 'hello',
    permalinkInput: '',
    layout: null,
    showComments: null,
    showRelated: null,
    showScheduleForm: false,
    scheduledAtInput: '',
    previewUrl: null,
    previewExpires: null,
    isPending: false,
    onSlugInputChange: vi.fn(),
    onPermalinkInputChange: vi.fn(),
    onScheduledAtChange: vi.fn(),
    onToggleScheduleForm: vi.fn(),
    onCancelScheduleForm: vi.fn(),
    onChangeStatus: vi.fn(),
    onChangeLayout: vi.fn(),
    onChangeShowComments: vi.fn(),
    onChangeShowRelated: vi.fn(),
    onSaveSlug: vi.fn(),
    onSavePermalink: vi.fn(),
    onSchedulePublish: vi.fn(),
    onCancelSchedule: vi.fn(),
    onGeneratePreview: vi.fn(),
    onRevokePreview: vi.fn(),
    ...overrides,
  }
}

describe('EntityStatusPanel — advanced custom permalink field (#651)', () => {
  it('hides the custom permalink field by default (advanced, opt-in)', () => {
    renderWithProviders(<EntityStatusPanel {...makeState()} />)

    // The disclosure toggle is present, but the field itself is not rendered.
    expect(screen.getByRole('button', { name: 'Advanced: custom URL' })).toBeInTheDocument()
    expect(screen.queryByLabelText('Custom permalink')).not.toBeInTheDocument()
  })

  it('reveals the permalink field on expand and wires change + save', async () => {
    const user = userEvent.setup()
    const onPermalinkInputChange = vi.fn()
    const onSavePermalink = vi.fn()
    renderWithProviders(
      <EntityStatusPanel {...makeState({ onPermalinkInputChange, onSavePermalink })} />,
    )

    await user.click(screen.getByRole('button', { name: 'Advanced: custom URL' }))

    const field = screen.getByLabelText('Custom permalink')
    expect(field).toBeInTheDocument()

    await user.type(field, '/x')
    expect(onPermalinkInputChange).toHaveBeenCalled()

    await user.click(screen.getByRole('button', { name: 'Save permalink' }))
    expect(onSavePermalink).toHaveBeenCalledTimes(1)
  })

  it('starts expanded and shows the value when a permalink is already set', () => {
    renderWithProviders(
      <EntityStatusPanel {...makeState({ permalinkInput: '/company/about/team' })} />,
    )

    expect(screen.getByLabelText('Custom permalink')).toHaveValue('/company/about/team')
  })
})
