# Nexora N0.30 — Commerce + Billing Foundation

N0.30 introduces provider-neutral Commerce records and public contracts. Core owns products, prices, currencies, explicit tax rules, customers, orders, invoices, transactions, refunds, subscriptions and billing events.

## Payment-provider boundary

Nexora Core does not embed Stripe, PayPal or any other gateway. A verified extension may implement `PaymentProviderContract` and register the adapter in `PaymentProviderRegistry`. Core persists provider references and transaction state but does not own provider private keys.

## Money

Billable amounts are integers in currency minor units. Decimal strings from Admin are converted deterministically according to the configured currency minor unit. No automatic FX conversion is performed.

## Taxes

The built-in tax calculator applies explicitly configured percentage rules. It is a calculation foundation, not a tax-compliance service; jurisdictions requiring automatic validation, nexus, VAT registration or filing should use a dedicated future provider extension.

## Historical integrity

Order items snapshot product name, SKU, quantity, price and tax results so later catalog edits do not change historical totals. Billing events and idempotency keys provide foundations for webhook/provider replay safety.

## Admin

Commerce has Overview, Products & Prices, Customers, Orders, Billing and Settings workspaces. All interactive controls consume `@nexora/admin-ui`.
