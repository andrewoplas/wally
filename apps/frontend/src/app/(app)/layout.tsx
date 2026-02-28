import { MessageCircle, CircleUser } from 'lucide-react';
import { AppSidebar } from '@/components/app/app-sidebar';
import { BottomTabBar } from '@/components/app/bottom-tab-bar';

export default function AppLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex h-screen overflow-hidden bg-muted">
      {/* Desktop sidebar */}
      <div className="hidden md:flex">
        <AppSidebar />
      </div>

      <div className="flex flex-1 flex-col overflow-hidden">
        {/* Mobile top bar */}
        <header className="flex items-center justify-between border-b border-sidebar-border bg-sidebar px-5 md:hidden" style={{ height: 56 }}>
          <div className="flex items-center gap-2">
            <div className="flex h-7 w-7 items-center justify-center rounded-[8px] bg-primary">
              <MessageCircle size={14} className="text-primary-foreground" />
            </div>
            <span className="font-heading text-[15px] font-bold text-foreground">Wally</span>
          </div>
          <div className="flex h-[30px] w-[30px] items-center justify-center rounded-full bg-primary">
            <span className="font-sans text-xs font-semibold text-primary-foreground">JD</span>
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-y-auto">
          <div className="mx-auto max-w-[991px] px-4 py-6 pb-6 md:px-16 md:py-12">{children}</div>
        </main>

        {/* Mobile bottom tab bar */}
        <BottomTabBar />
      </div>
    </div>
  );
}
