'use client';

import { useState, useEffect, useCallback } from 'react';
import { MessageCircle } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface FeedbackRow {
  id: string;
  type: 'rating' | 'general';
  rating: string | null;
  message: string | null;
  category: string | null;
  email: string | null;
  name: string | null;
  source: 'plugin' | 'website';
  site_id: string | null;
  conversation_id: string | null;
  message_id: string | null;
  created_at: string;
}

const PAGE_SIZE = 50;
const STORAGE_KEY = 'wally_admin_token';

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function Badge({ label, variant }: { label: string; variant: 'primary' | 'secondary' | 'muted' }) {
  const styles = {
    primary: 'bg-primary/10 text-primary',
    secondary: 'bg-[hsl(var(--color-success-subtle))] text-[hsl(var(--color-success-text))]',
    muted: 'bg-muted text-muted-foreground',
  };
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 font-sans text-xs font-medium ${styles[variant]}`}>
      {label}
    </span>
  );
}

function ExpandableText({ text }: { text: string }) {
  const [expanded, setExpanded] = useState(false);
  const short = text.length > 120;
  return (
    <span className="font-sans text-sm text-foreground">
      {expanded || !short ? text : `${text.slice(0, 120)}…`}
      {short && (
        <button
          onClick={() => setExpanded((v) => !v)}
          className="ml-1 font-sans text-xs text-primary hover:underline"
        >
          {expanded ? 'less' : 'more'}
        </button>
      )}
    </span>
  );
}

// ── Password gate ────────────────────────────────────────────────────────────

function LoginForm({ onSuccess }: { onSuccess: (token: string) => void }) {
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await fetch('/api/admin/auth', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ password }),
      });
      const data = await res.json();
      if (res.ok && data.token) {
        localStorage.setItem(STORAGE_KEY, data.token);
        onSuccess(data.token);
      } else {
        setError(data.error ?? 'Invalid password.');
      }
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-muted px-4">
      <div className="w-full max-w-sm">
        {/* Logo */}
        <div className="mb-8 flex flex-col items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-[14px] bg-primary">
            <MessageCircle size={22} className="text-primary-foreground" />
          </div>
          <div className="text-center">
            <h1 className="font-heading text-2xl font-bold text-foreground">Wally Admin</h1>
            <p className="mt-1 font-sans text-sm text-muted-foreground">Enter your password to view feedback</p>
          </div>
        </div>

        {/* Card */}
        <div className="rounded-[20px] border border-border bg-white p-6 shadow-sm">
          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <div className="flex flex-col gap-1.5">
              <label className="font-sans text-sm font-medium text-foreground">Password</label>
              <Input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                autoFocus
              />
              {error && <p className="font-sans text-sm text-destructive">{error}</p>}
            </div>
            <Button
              type="submit"
              variant="solid-primary"
              size="md"
              disabled={loading || !password}
              className="w-full justify-center"
            >
              {loading ? 'Signing in…' : 'Sign in'}
            </Button>
          </form>
        </div>
      </div>
    </div>
  );
}

// ── Filter select ────────────────────────────────────────────────────────────

function FilterSelect({
  value,
  onChange,
  children,
}: {
  value: string;
  onChange: (v: string) => void;
  children: React.ReactNode;
}) {
  return (
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="h-10 rounded-[12px] border border-border bg-white px-3 font-sans text-sm text-foreground outline-none focus:border-primary"
    >
      {children}
    </select>
  );
}

// ── Feedback table ───────────────────────────────────────────────────────────

function FeedbackTable({ token }: { token: string }) {
  const [rows, setRows] = useState<FeedbackRow[]>([]);
  const [count, setCount] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [offset, setOffset] = useState(0);
  const [filters, setFilters] = useState({ type: '', source: '', category: '' });

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError('');
    const params = new URLSearchParams({ limit: String(PAGE_SIZE), offset: String(offset) });
    if (filters.type) params.set('type', filters.type);
    if (filters.source) params.set('source', filters.source);
    if (filters.category) params.set('category', filters.category);

    try {
      const res = await fetch(`/api/admin/feedback?${params.toString()}`, {
        headers: { 'X-Admin-Token': token },
      });
      if (!res.ok) {
        setError('Failed to load feedback.');
        return;
      }
      const json = await res.json();
      setRows(json.data ?? []);
      setCount(json.count ?? 0);
    } catch {
      setError('Network error.');
    } finally {
      setLoading(false);
    }
  }, [offset, filters]);

  useEffect(() => {
    void fetchData();
  }, [fetchData]);

  function handleFilterChange(key: keyof typeof filters, value: string) {
    setOffset(0);
    setFilters((f) => ({ ...f, [key]: value }));
  }

  function handleLogout() {
    localStorage.removeItem(STORAGE_KEY);
    window.location.reload();
  }

  const totalPages = Math.ceil(count / PAGE_SIZE);
  const currentPage = Math.floor(offset / PAGE_SIZE) + 1;

  return (
    <div className="min-h-screen bg-muted">
      {/* Header */}
      <header className="border-b border-border bg-white px-6 py-4">
        <div className="mx-auto flex max-w-7xl items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-8 w-8 items-center justify-center rounded-[10px] bg-primary">
              <MessageCircle size={15} className="text-primary-foreground" />
            </div>
            <div>
              <h1 className="font-heading text-[17px] font-bold text-foreground">Feedback Dashboard</h1>
              <p className="font-sans text-xs text-muted-foreground">{count} total entries</p>
            </div>
          </div>
          <Button variant="outline" size="sm" onClick={handleLogout}>
            Sign out
          </Button>
        </div>
      </header>

      <main className="mx-auto max-w-7xl px-6 py-6">
        {/* Filters */}
        <div className="mb-5 flex flex-wrap items-center gap-3">
          <FilterSelect value={filters.type} onChange={(v) => handleFilterChange('type', v)}>
            <option value="">All types</option>
            <option value="rating">Rating</option>
            <option value="general">General</option>
          </FilterSelect>
          <FilterSelect value={filters.source} onChange={(v) => handleFilterChange('source', v)}>
            <option value="">All sources</option>
            <option value="plugin">Plugin</option>
            <option value="website">Website</option>
          </FilterSelect>
          <FilterSelect value={filters.category} onChange={(v) => handleFilterChange('category', v)}>
            <option value="">All categories</option>
            <option value="bug">Bug</option>
            <option value="feature">Feature</option>
            <option value="general">General</option>
          </FilterSelect>
          <Button variant="secondary" size="sm" onClick={fetchData}>
            Refresh
          </Button>
        </div>

        {/* Content */}
        {error ? (
          <div className="rounded-[16px] border border-[hsl(var(--color-error))] bg-[hsl(var(--color-error))] p-4 font-sans text-sm text-[hsl(var(--color-error-foreground))]">
            {error}
          </div>
        ) : loading ? (
          <div className="space-y-2">
            {Array.from({ length: 8 }).map((_, i) => (
              <div key={i} className="h-14 animate-pulse rounded-[16px] bg-white/60" />
            ))}
          </div>
        ) : rows.length === 0 ? (
          <div className="rounded-[20px] border border-border bg-white p-12 text-center font-sans text-sm text-muted-foreground">
            No feedback found.
          </div>
        ) : (
          <div className="overflow-x-auto rounded-[20px] border border-border bg-white shadow-sm">
            <table className="w-full font-sans text-sm">
              <thead>
                <tr className="border-b border-border bg-muted text-left">
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Date</th>
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Type</th>
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Source</th>
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Category</th>
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Rating</th>
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Name / Email</th>
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Message</th>
                  <th className="px-4 py-3 font-sans text-xs font-medium uppercase tracking-wide text-muted-foreground">Site ID</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {rows.map((row) => (
                  <tr key={row.id} className="transition-colors hover:bg-muted/50">
                    <td className="whitespace-nowrap px-4 py-3 font-sans text-xs text-muted-foreground">
                      {formatDate(row.created_at)}
                    </td>
                    <td className="px-4 py-3">
                      <Badge label={row.type} variant={row.type === 'rating' ? 'primary' : 'muted'} />
                    </td>
                    <td className="px-4 py-3">
                      <Badge label={row.source} variant={row.source === 'plugin' ? 'secondary' : 'muted'} />
                    </td>
                    <td className="px-4 py-3 font-sans text-sm text-muted-foreground">{row.category ?? '—'}</td>
                    <td className="px-4 py-3 font-sans text-sm text-foreground">
                      {row.rating === 'thumbs_up' ? '👍' : row.rating === 'thumbs_down' ? '👎' : row.rating ?? '—'}
                    </td>
                    <td className="px-4 py-3">
                      <div className="font-sans text-sm font-medium text-foreground">{row.name ?? '—'}</div>
                      <div className="font-sans text-xs text-muted-foreground">{row.email ?? ''}</div>
                    </td>
                    <td className="max-w-xs px-4 py-3">
                      {row.message ? <ExpandableText text={row.message} /> : <span className="text-muted-foreground">—</span>}
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                      {row.site_id ? `${row.site_id.slice(0, 12)}…` : '—'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {!loading && totalPages > 1 && (
          <div className="mt-5 flex items-center justify-between">
            <p className="font-sans text-sm text-muted-foreground">
              Page {currentPage} of {totalPages}
            </p>
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={offset === 0}
                onClick={() => setOffset((o) => Math.max(0, o - PAGE_SIZE))}
              >
                Previous
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={offset + PAGE_SIZE >= count}
                onClick={() => setOffset((o) => o + PAGE_SIZE)}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}

// ── Root component ───────────────────────────────────────────────────────────

export function FeedbackDashboard() {
  const [token, setToken] = useState<string | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    setToken(localStorage.getItem(STORAGE_KEY));
    setReady(true);
  }, []);

  if (!ready) return null;

  if (!token) {
    return <LoginForm onSuccess={(t) => setToken(t)} />;
  }

  return <FeedbackTable token={token} />;
}
