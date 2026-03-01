import Link from 'next/link';

export function LegalFooter() {
  return (
    <footer className="flex h-16 items-center justify-between border-t border-border px-20">
      <span className="text-[13px] text-muted-foreground">© 2026 Wally. All rights reserved.</span>
      <div className="flex items-center gap-6">
        <Link href="/privacy" className="text-[13px] text-muted-foreground hover:text-foreground">
          Privacy Policy
        </Link>
        <Link href="/terms" className="text-[13px] text-muted-foreground hover:text-foreground">
          Terms of Service
        </Link>
      </div>
    </footer>
  );
}
