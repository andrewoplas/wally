import { AccountForm } from '@/components/app/account-form';

export default function AccountPage() {
  return (
    <div className="flex flex-col gap-8">
      {/* Page header */}
      <div className="flex flex-col gap-1">
        <h1 className="font-heading text-[28px] font-bold text-foreground">Account</h1>
        <p className="font-sans text-sm leading-[1.5] text-muted-foreground">
          Manage your personal details and password.
        </p>
      </div>

      <AccountForm />
    </div>
  );
}
