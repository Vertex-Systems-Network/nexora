# Untitled UI adapter layer

Nexora feature code must import from `@nexora/admin-ui`, never directly from this directory.

This layer intentionally follows Untitled UI React's source-owned model. Add/upgrade official MIT components here with the Untitled UI CLI when needed, then keep Nexora-specific behavior in the public wrappers. This protects modules from vendor churn and keeps spacing, loading, accessibility, and appearance policies centralized.
