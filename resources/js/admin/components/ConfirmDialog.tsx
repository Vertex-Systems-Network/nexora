import { Button, Modal } from "@nexora/admin-ui";

export function ConfirmDialog({open,title,description,confirmLabel="Confirm",danger=true,processing=false,onCancel,onConfirm}:{open:boolean;title:string;description:string;confirmLabel?:string;danger?:boolean;processing?:boolean;onCancel:()=>void;onConfirm:()=>void}){
 return <Modal open={open} onClose={onCancel} title={title} description={description} footer={<><Button variant="secondary" onClick={onCancel} disabled={processing}>Cancel</Button><Button variant={danger?"danger":"primary"} loading={processing} onClick={onConfirm}>{confirmLabel}</Button></>}/>;
}
