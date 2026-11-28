# ResearchBrief — SEC-THEME-CI-DEPS-001

## Problem

The existing security audit prevents theme lifecycle verification from reaching PHPUnit.

## Users & VOC evidence

Developers and operators require the existing security gate to pass without ignored advisories. Evidence is the repository requirement and CI job 140789486157; no customer interview is claimed.

## Baseline

At source 15967f1f3550a263cb1bf0ca2e557230b8e1a682, governance run 46084084434 reports 25 advisories affecting 8 locked Composer packages. PHPUnit does not execute in that job.

## Alternatives

Bypass/ignore the audit: rejected. Blindly merge dependency PRs: rejected (#170 only targets WebAuthn 5.3.12, below the observed fixed range). Prepare a minimal same-family patch candidate: selected; independently review before root promotion.

## CTQs

Only the eight affected packages change; root constraints and locks remain unchanged during candidate preparation; the isolated locked audit finds zero advisories; existing candidate provenance and compatibility checks pass; independent review and governed promotion remain pending until actually completed.

## Confidence

low — audit and source preparation only; target and compatibility acceptance remain separate.

## Sources

CI run 46084084434, job 140789486157; composer.json/composer.lock at source 15967f1; existing dependency intake/review/promotion scripts.
