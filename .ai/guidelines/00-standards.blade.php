# MANDATORY COMPLIANCE & STANDARDS

**CRITICAL**: Every item in this file is a MANDATORY requirement. This document serves as the authoritative preamble for CLAUDE.md.

---

{!! Blade::render(file_get_contents(base_path('.ai/guidelines/stubs/compliance.stub')), ['assist' => $assist]) !!}

---

{!! Blade::render(file_get_contents(base_path('.ai/guidelines/stubs/architecture.stub')), ['assist' => $assist]) !!}

---

{!! Blade::render(file_get_contents(base_path('.ai/guidelines/stubs/quality.stub')), ['assist' => $assist]) !!}

---

{!! Blade::render(file_get_contents(base_path('.ai/guidelines/stubs/behavior.stub')), ['assist' => $assist]) !!}

---

@if(file_exists(base_path('.ai/guidelines/stubs/session-learnings.stub')))
    {!! Blade::render(file_get_contents(base_path('.ai/guidelines/stubs/session-learnings.stub')), ['assist' => $assist]) !!}
@endif

---

**Remember: Every single item in this document is a requirement, not a suggestion.**
