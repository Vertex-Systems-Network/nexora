import { useMemo, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Badge, Button, Input, Modal, Select } from "@nexora/admin-ui";
import { CommerceNav } from "./_CommerceNav";

type Row = {
    id: string;
    number: string;
    status: string;
    customer: string;
    email: string | null;
    items_count: number;
    has_invoice: boolean;
    currency: string;
    total: string;
    paid: string;
    created_at: string | null;
};
type Props = {
    orders: Paginator<Row>;
    customers: Array<{ id: string; name: string; email: string }>;
    prices: Array<{ id: string; label: string; currency: string }>;
    currencies: Array<{ code: string; name: string }>;
    canManage: boolean;
    canBill: boolean;
};

const human = (value: string) => value.replace(/[_-]+/g, " ").replace(/\b\w/g, (character) => character.toUpperCase());

export default function Orders({ orders, customers, prices, currencies, canManage, canBill }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ customer_id: "", currency: currencies[0]?.code ?? "USD", price_id: "", quantity: "1" });
    const priceOptions = prices.filter((price) => price.currency === form.data.currency).map((price) => ({ value: price.id, label: price.label }));

    const columns = useMemo<Column<Row>[]>(() => [
        {
            key: "order",
            label: "Order",
            render: (row) => <div><p className="font-semibold text-[var(--nx-text)]">{row.number}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{row.customer} · {row.items_count} item{row.items_count === 1 ? "" : "s"}</p></div>,
        },
        {
            key: "status",
            label: "Status",
            render: (row) => <Badge tone={row.status === "paid" || row.status === "completed" ? "success" : row.status === "cancelled" || row.status === "refunded" ? "neutral" : "warning"}>{human(row.status)}</Badge>,
        },
        {
            key: "total",
            label: "Total",
            render: (row) => <div><p className="font-semibold text-[var(--nx-text)]">{row.total}</p><p className="text-xs text-[var(--nx-text-muted)]">Paid {row.paid}</p></div>,
        },
        {
            key: "actions",
            label: "",
            className: "w-52 text-right",
            render: (row) => (
                <div className="flex justify-end gap-2">
                    {canManage && row.status === "draft" && (
                        <Button size="sm" variant="secondary" onClick={() => router.post(`/admin/commerce/orders/${row.id}/place`, {}, { preserveScroll: true })}>
                            Place
                        </Button>
                    )}
                    {canBill && row.status !== "cancelled" && (
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => row.has_invoice
                                ? router.visit("/admin/commerce/billing")
                                : router.post(`/admin/commerce/orders/${row.id}/invoice`, {}, { preserveScroll: true })}
                        >
                            {row.has_invoice ? "Billing" : "Invoice"}
                        </Button>
                    )}
                </div>
            ),
        },
    ], [canManage, canBill]);

    return <AdminLayout>
        <Head title="Commerce Orders" />
        <PageHeader
            eyebrow="Commerce"
            title="Orders"
            description="Orders use immutable monetary snapshots so later product-price changes do not rewrite historical totals."
            actions={canManage ? <Button onClick={() => setOpen(true)}>Create draft order</Button> : undefined}
        />
        <CommerceNav current="/admin/commerce/orders" />
        <DataTable rows={orders.data} columns={columns} paginator={orders} empty={<EmptyState title="No orders" description="Create a draft order from an active Commerce price." />} />
        <Modal
            open={open}
            onClose={() => setOpen(false)}
            title="Create draft order"
            description="This foundation creates one line item per draft. Multi-line checkout composition is exposed through Commerce services for future checkout extensions."
            footer={<><Button variant="secondary" onClick={() => setOpen(false)}>Cancel</Button><Button loading={form.processing} onClick={() => form.post("/admin/commerce/orders", { preserveScroll: true, onSuccess: () => { form.reset(); setOpen(false); } })}>Create order</Button></>}
        >
            <div className="grid gap-4">
                <Select label="Customer" value={form.data.customer_id} onChange={(value) => form.setData("customer_id", value)} options={[{ value: "", label: "Guest customer" }, ...customers.map((customer) => ({ value: customer.id, label: `${customer.name} — ${customer.email}` }))]} />
                <Select label="Currency" value={form.data.currency} onChange={(value) => { form.setData("currency", value); form.setData("price_id", ""); }} options={currencies.map((currency) => ({ value: currency.code, label: `${currency.code} — ${currency.name}` }))} />
                <Select label="Product price" value={form.data.price_id} onChange={(value) => form.setData("price_id", value)} options={priceOptions} placeholder="Choose an active product price" />
                <Input label="Quantity" value={form.data.quantity} onChange={(event) => form.setData("quantity", event.target.value)} inputMode="numeric" error={form.errors.quantity} />
            </div>
        </Modal>
    </AdminLayout>;
}
