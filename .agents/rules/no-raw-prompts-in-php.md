# Rule: No Raw Prompts in PHP Files

**Glob**: `app/**/*.php`

## Rule Statement

Never hardcode raw AI prompt text directly in PHP controllers, actions, jobs, agents, services, or models.

## Rationale & Guidance

1. **Centralization**: Each part of a prompt has one home (ADR 0005):
    - An AI Agent's editable instructions (role, rules, style): `App\Domains\Ai\Prompts\DefaultAgentPrompts`.
    - An AI Agent's locked contract (required context and output format, with `{{placeholders}}`): `App\Domains\Ai\Prompts\AgentContracts`.
    - User messages sent to an agent: `App\Domains\Ai\Services\AiPromptManager` templates, filled with `userPrompt()`.
    - The system prompt is always built by `AgentConfigResolver::instructions()`; never concatenate one in an action or job.
2. **Maintainability**: Keep prompt wording separate from application flow so prompts can be reviewed and changed without editing business logic.
3. **Composition**: Pass runtime data into a named prompt template through explicit variables or context rather than assembling large inline strings in PHP.
