# DORA AI integration guide

## Current behavior

DORA stores owner-scoped conversations and user messages. The chat endpoint uses server-sent events, but buffers Gemini's SSE response through cURL before forwarding it; this avoids PHP's HTTPS stream-wrapper failure on some Windows setups, at the cost of showing the assistant text after generation completes rather than token-by-token. API clients continue to receive JSON unless they request `text/event-stream`. AI remains disabled by default; when disabled or incompletely configured, ordinary chat returns HTTP 503 with `provider_unavailable`, keeps the user message, and does not fabricate an assistant response. An explicit confirmation or cancellation for a saved Tenant request or Admin announcement draft is handled by the backend without requiring AI availability. The UI offers retry after a provider failure.

Gemini and Qdrant use Laravel's HTTP client; no provider SDK or vector database package is required. The selected providers are resolved from configuration, and unconfigured implementations remain the fallback for unsupported provider selections.

## Architecture

- `AIProviderInterface` receives a `ChatPrompt` containing trusted system instructions, conversation messages, an optional future conversation summary, approved retrieved chunks, the actor audience, and the authorized tool definitions. It returns a `ChatCompletion` containing text, optional tool calls, and IDs of cited knowledge chunks.
- `EmbeddingProviderInterface` creates query embeddings with Gemini's `RETRIEVAL_QUERY` task type and document embeddings with `RETRIEVAL_DOCUMENT`.
- `VectorSearchInterface` accepts a vector, audience, and bounded result limit. Qdrant filters audience, approval, effective/expiry dates, and optional namespace before returning `KnowledgeChunk` values; `KnowledgeRetrievalService` independently validates metadata and applies the same authorization/date checks.
- `DocumentIngestionInterface::ingest()` accepts an approved document and scalar metadata. The Qdrant adapter chunks it, stores provenance and approval metadata, upserts stable chunk IDs, removes obsolete chunks on reindex, and exposes `deleteSourceDocument()` for explicit source deletion.
- `KnowledgeRetrievalService` coordinates embedding and retrieval. `ChatService` applies DORA's trusted instructions, limits conversation context and tool-call rounds, validates cited source IDs, and stores assistant messages only after a provider response succeeds.
- `ChatToolRegistry` applies server-side role checks and validates tool arguments. Tenant-specific methods receive the authenticated `User` from the request; tool arguments never accept a tenant ID. Admin operational tools and announcement creation are available only to admins. Tenant requests and admin announcements prepare expiring drafts; `ChatService` handles an explicit confirmation directly through the corresponding server-side service, without asking Gemini to authorize or execute the write. If Gemini returns a tool call, any intermediate streamed text is cleared before tool execution continues.
- `ConversationService` scopes signed-in conversations to `user_id` and guest conversations to an HMAC of the Laravel session ID. Conversation and message payloads omit owner/session identifiers.

Gemini receives trusted instructions through its separate `systemInstruction` field. User history, retrieved documents, summaries, and tool results remain data; retrieved content is serialized separately and never grants permissions. Tool names are limited to the definitions from `ChatToolRegistry`, and calls are authorized and validated by that registry before execution.

## Role-aware tools and request confirmation

| Tool | Guest | Tenant | Admin |
|---|---:|---:|---:|
| `get_current_user` | Yes | Own identity | Own identity |
| `get_dormitory_information` | Yes | Yes | Yes |
| `get_room_availability` | Yes | Yes | Yes |
| `get_announcements` | Yes | Yes | Yes |
| `get_tenant_room` | No | Own account | No |
| `get_tenant_balance` | No | Own account | No |
| `get_payment_history` | No | Own account | No |
| `get_electricity_bills` | No | Own account | No |
| `get_maintenance_status` | No | Own account | No |
| `get_absence_status` | No | Own account | No |
| `get_application_status` | No | Own account | No |
| `get_room_occupancy_summary` | No | No | Yes |
| `get_admin_overview` | No | No | Yes |
| `get_admin_tenants` | No | No | Yes |
| `get_admin_pending_applications` | No | No | Yes |
| `get_admin_pending_absences` | No | No | Yes |
| `get_admin_maintenance_requests` | No | No | Yes |
| `get_admin_electricity_bills` | No | No | Yes |
| `prepare_absence_request` | No | Yes, draft only | No |
| `prepare_maintenance_request` | No | Yes, draft only | No |
| `prepare_announcement` | No | No | Yes, draft only |

The server provides the model with a trusted, authenticated name and role context; internal IDs remain server-side metadata and are not included in tool results. The model cannot choose the actor or tenant ID for any tool. Tenant-specific results query only the authenticated tenant's records.

Electricity tools return finalized stored allocations and chargeable-day snapshots; they do not recalculate or modify bills. Room availability reports reserved/occupied assignments and remaining capacity without exposing tenant identities. Admin tools provide bounded live summaries and records, and are protected by both role-filtered declarations and server-side authorization.

Tenant request tools validate and save a 15-minute, conversation-bound draft only. DORA must show the details and ask the tenant to reply `Confirm` or `Cancel`; only an exact explicit confirmation submitted as the conversation owner can create a pending absence or maintenance request. Replays of the same confirmation message return its saved result, and expired drafts cannot be submitted. Absence dates overlapping pending or approved requests on the active room assignment are rejected; dates from rejected requests may be reused. The existing tenant forms use the same validated request service.

Admins can ask DORA to draft an announcement using the existing categories, title, body, optional end date, pin setting, and immediate or scheduled publication. DORA saves no announcement until the owning admin explicitly confirms the displayed draft. Confirmed announcements are attributed to the authenticated admin; canceled and expired drafts are not published. Tenants and guests cannot use this tool. DORA cannot approve requests, modify payments, assign rooms, or perform other administrative writes.

## Environment configuration

Set secrets in the deployment secret manager or an uncommitted local environment file. Do not expose them through `VITE_*` variables or return them in API responses.

| Variable | Purpose |
|---|---|
| `DORA_AI_ENABLED` | Explicit opt-in switch; defaults to `false`. |
| `DORA_LLM_PROVIDER`, `DORA_LLM_MODEL` | `gemini` provider and exact supported Gemini API model ID. No model ID is assumed. |
| `DORA_LLM_API_KEY`, `DORA_LLM_BASE_URL`, `DORA_LLM_TIMEOUT` | Server-side credentials, Gemini API base URL, and request timeout in seconds (defaults to `30`). |
| `DORA_EMBEDDING_PROVIDER`, `DORA_EMBEDDING_MODEL` | `gemini` provider and exact supported Gemini embedding model ID. |
| `DORA_EMBEDDING_API_KEY`, `DORA_EMBEDDING_BASE_URL`, `DORA_EMBEDDING_TIMEOUT`, `DORA_EMBEDDING_DIMENSIONS` | Server-side embedding configuration, timeout (defaults to `30` seconds), and a dimension supported by the selected model. It must match Qdrant. |
| `DORA_VECTOR_PROVIDER`, `DORA_VECTOR_ENDPOINT`, `DORA_VECTOR_API_KEY` | `qdrant` provider, endpoint, and optional server-side API key. |
| `DORA_VECTOR_INDEX`, `DORA_VECTOR_NAMESPACE` | Qdrant collection name and optional payload namespace. |
| `DORA_RETRIEVAL_LIMIT` | Maximum retrieved chunks per question; defaults to `5`. |
| `DORA_CHAT_HISTORY_LIMIT` | Conversation messages supplied to the model; defaults to `12`. |
| `DORA_MAX_TOOL_ROUNDS` | Tool-call loop bound; defaults to `3` and is capped at `5`. |

If `DORA_EMBEDDING_API_KEY` is empty, the embedding adapter may use `DORA_LLM_API_KEY` only when both configured providers are Gemini. Gemini uses the same API key mechanism for both APIs. LLM and embedding request timeouts are configured separately.

Setting credentials does not enable AI. `DORA_AI_ENABLED` must be explicitly set to `true`, all providers must validate as configured, and approved knowledge must be indexed. Keep it `false` during setup.

## Adapter inputs and outputs

Adapters implement the contracts in `app/Contracts/Chat/`:

1. The Gemini LLM adapter accepts `ChatPrompt`, uses trusted system instructions separately from user/document content, validates tool calls against prompt definitions, and returns `ChatCompletion`. Provider errors are surfaced without API keys or provider response bodies.
2. The Gemini embedding adapter validates finite numeric vectors and exact configured dimensions. `embed()` is for retrieval queries; `embedDocument()` is for ingested documents.
3. The Qdrant adapter creates a cosine collection with configured dimensions when ingestion first needs it. Search applies audience, approval, date, and namespace filters in Qdrant and validates returned payloads before creating `KnowledgeChunk` values.
4. The ingestion adapter accepts only approved content with a restricted metadata allowlist. It requires stable document ID, source, version, audience, and approval status; reusing the same ID replaces that source's chunks.

`DocumentIngestionInterface` gained `deleteSourceDocument(string $documentId)` to support source deletion without changing the existing `ingest(string $content, array $metadata): void` call. `EmbeddingProviderInterface` gained `embedDocument(string $text)` because Gemini uses different task types for query and document vectors.

## Conversation and privacy notes

Conversation content is stored because users need history; users should not enter passwords, invitation links, payment-card information, or other unnecessary sensitive data. Guest history is accessible only from the same Laravel session. Authenticated history is scoped to the owner, and deletion cascades to its messages. This foundation does not define a retention schedule or automated expiry; deployers should set a retention policy before production use.

## Local operations

1. Start the existing Qdrant container without recreating or deleting its persistent volume:

   ```powershell
   docker start dora-qdrant
   Invoke-RestMethod http://localhost:6333/healthz
   ```

2. Configure `.env` with valid API credentials, exact Gemini API model IDs, the supported embedding output dimension, and the same vector provider/collection/namespace settings used for ingestion. Keep `DORA_AI_ENABLED=false` during setup. Then clear cached configuration:

   ```powershell
   php artisan config:clear
   ```

3. Qdrant creates the configured collection automatically on the first successful ingestion if it does not exist. It uses `DORA_EMBEDDING_DIMENSIONS` and cosine distance, and creates datetime payload indexes for `effective_from` and `expires_at` as needed for filtered retrieval. Existing indexes on those fields must also use the `datetime` type. To create the collection manually, replace the dimension below with a value supported by the selected embedding model and configure the same value in `.env`:

   ```powershell
   $dimensions = <configured-dimension>
   $body = @{ vectors = @{ size = $dimensions; distance = 'Cosine' } } | ConvertTo-Json -Depth 4
   Invoke-RestMethod -Method Put `
     -Uri 'http://localhost:6333/collections/dora_knowledge' `
     -ContentType 'application/json' -Body $body
   ```

   Do not delete or recreate an existing collection to resolve a dimension mismatch. Confirm its configuration first:

   ```powershell
   (Invoke-RestMethod http://localhost:6333/collections/dora_knowledge).result.config.params.vectors
   ```

   The reported `size` must equal `DORA_EMBEDDING_DIMENSIONS`, and `distance` must be `Cosine`. Use the configured collection name if it is not `dora_knowledge`.

4. Put a reviewed, approved policy or handbook document under `storage/app/private/dora/knowledge`. Ingest it using a stable ID and an explicit approval flag:

   ```powershell
   New-Item -ItemType Directory -Force storage\app\private\dora\knowledge
   php artisan dora:ingest-document handbook.md `
     --id=dormitory-handbook --source='Dormitory handbook' `
     --document-version=2026-11 --audience=guest --approved
   ```

   `path` is relative to that directory. The command rejects paths outside it, unapproved documents, unsupported metadata, and obvious credential/token assignments. Use `--audience=tenant` or `admin` only for documents approved for those audiences. Do not ingest tenant account records, credentials, invitation links, or personal data; source content still requires human review.

5. To reindex an edited document, invoke the same command with the same `--id` and an incremented `--document-version`. Existing chunk IDs are upserted and obsolete chunks are removed. To remove a source entirely:

   ```powershell
   php artisan dora:delete-document dormitory-handbook
   ```

   These commands delete only points for the specified source in the configured namespace; they do not delete the collection or Docker volume.

6. Run the integration tests before explicitly enabling the chat:

   ```powershell
   php artisan test --compact tests/Feature/GeminiAIProviderTest.php
   php artisan test --compact tests/Feature/GeminiEmbeddingProviderTest.php
   php artisan test --compact tests/Feature/QdrantVectorSearchTest.php
   php artisan test --compact tests/Feature/QdrantDocumentIngestionTest.php
   php artisan test --compact tests/Feature/KnowledgeRetrievalServiceTest.php
   php artisan test --compact tests/Feature/DoraDocumentCommandsTest.php
   ```

   Only after configuration, collection, and approved content checks pass should an operator set `DORA_AI_ENABLED=true`, clear/rebuild config cache, and run the complete suite with `php artisan test --compact`.
