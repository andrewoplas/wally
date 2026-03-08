import type { Metadata } from 'next';
import { FeedbackDashboard } from '@/components/admin/feedback-dashboard';

export const metadata: Metadata = {
  title: 'Feedback Admin | Wally',
  robots: { index: false, follow: false },
};

export default function AdminFeedbackPage() {
  return <FeedbackDashboard />;
}
