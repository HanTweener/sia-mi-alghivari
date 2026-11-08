# Rebuild verification

Run: 29 October 2026, local built preview at `http://127.0.0.1:5173/`, Chromium headless 156.0.8078.4. The final browser run loaded `assets/main-DOaH4Q7u.js`, recorded in `evidence/interaction-results.json`. Main reran the 13 browser cases and 16 layouts after the final desktop width adjustment; the independent focus retest used the preceding main-D7RavuIW build with the same interaction logic. This is the isolated React prototype, with synthetic fixtures and session memory. It is not a production release assessment.

## Executed checks

| Scope | Result | Evidence |
| --- | --- | --- |
| Unit interaction tests | PASS — 8/8 | `npm test`, `prototype/src/App.test.tsx` |
| Browser happy paths and negative boundaries | PASS — 13/13 | `evidence/interaction-results.json`, selectors T01–T13 in `check-interactions.mjs` |
| Responsive layout | PASS — 16 screen/width combinations | `evidence/layout-inspection.json`, screenshots `{grades,attendance,progress,report}-{320,390,768,1440}.png` |
| Layout invariants | PASS | No document overflow; one h1; visible sampled main text at least 14px; expanded scenario select text fits at all four widths |
| Grade density at 390×844 | PASS after correction | First grade input y=503px, 59.6% of viewport; requested diagnostic target ≤60% |
| Production APIs, persistent writes, authentication and tenant isolation | N/A to this prototype | No backend or real accounts are implemented; UI scenario navigation does not provide authorization |
| Real devices, Firefox, Safari, screen reader, 200% zoom | NOT VERIFIED | No executed evidence |

T01–T03 verify eight grade inputs, blank distinct from zero, comma/dot decimals, real search and filtering, and invalid format/range blocking. T04–T06 verify memory-only save/reload, partial sharing with confirmation, zero exposed in the correct child's shared snapshot, edit locking, focus trapping/restoration and dirty navigation. T07 verifies per-meeting explicit attendance selection, required save before signing, locked signed inputs and guardian snapshot. T08–T11 verify simulated failure-state recovery, child/subject filters, empty report recovery, keyboard skip link, 320px dialog fit, reduced motion and multi-digit editing in the blank-only filter. T12 exercises the compact mobile menu at 390×844: Escape restores the menu opener, destination selection focuses main, dirty cancellation by button and Escape focuses main while preserving the edited value, and discard focuses main and restores the saved value. T13 confirms saved drafts and unsigned attendance remain absent from guardian progress. Unit tests also confirm score-format help remains an accessible description after moving below the roster.

## Observations and limits

The initial T12 run asserted focus before Radix's deferred close autofocus completed. That failure is retained in `evidence/rebuild-initial-interaction-results.json`. The test now waits two animation frames after the menu closes; the rerun passed without a product change for that assertion. A later T05 immediate autofocus assertion had the same timing issue, retained in `evidence/rebuild-review-focus-timing-results.json`; it now uses the same synchronization. The final 13-case rerun passed. Separately, the integrating agent corrected navigation focus after the mobile menu disappears and dirty-dialog focus when its opener is disconnected or is the document body. T12 now checks those outcomes explicitly.

The first build placed the first score at y=593 (70.3%) on a 390×844 viewport. The integrating agent compacted status metadata and moved score-format guidance below the table, linked to inputs with `aria-describedby`. Rechecking measured y=503 (59.6%), satisfying the diagnostic density target. Source: `prototype/src/components/GradeRegister.tsx`; current evidence: `evidence/grades-390.png`; historical measurements: `evidence/rebuild-round1-layout-inspection.json`.

`DESIGN_EVALUATION.md` was consulted. Relevant keyboard, focus, dialog, reduced-motion and responsive observations were executed; these checks do not constitute WCAG certification. No score is assigned to unverified accessibility dimensions. Brand identity remains a product decision.

No persisted/API side effects, real notification delivery or production permissions were claimed. The save/share/sign workflows are explicitly synthetic in-memory interactions. A new production implementation requires its own positive/negative API and authorization tests.

Integrator subsequently executed Prettier check PASS, explicit source structure audit26 files PASS (0 errors/warnings), styling boundary PASS (0 manual component CSS/inline styles), served-build SHA comparison PASS and standalone HTML checks4/4 offline PASS. PDF14 pages were exported, rendered through Poppler and visually reviewed in `../evidence/rebuild-pdf-contact.png`. These are integrator results, separate from the independent QA execution above. Current artifact fingerprints: `../evidence/rebuild-artifact-sha256.json`; source fingerprints: `evidence/source-sha256.json`.
