'use client';

import * as Accordion from '@radix-ui/react-accordion';
import { ChevronDown } from 'lucide-react';

const FAQS = [
  {
    q: 'Can I cancel anytime?',
    a: 'Yes. You can cancel your subscription at any time from your account settings. Your plan remains active until the end of the current billing period — no surprise charges, no questions asked.',
  },
  {
    q: 'What happens to my sites if I downgrade?',
    a: 'Your activated sites will be deactivated and the plugin will stop responding to commands. It remains installed and no data is deleted — resubscribe anytime to reactivate.',
  },
  {
    q: 'Is my WordPress data safe?',
    a: 'Yes. Wally never stores your WordPress content on our servers. Messages are processed in real time and only the minimum context needed to complete your request is sent to the AI model. Your site credentials are never exposed — all actions execute locally on your WordPress server.',
  },
  {
    q: 'Which AI models does Wally use?',
    a: 'Wally is powered by leading models from Anthropic (Claude) and OpenAI. You can bring your own API key (BYOK) to use your existing credits, or let Wally handle it with the included quota on paid plans.',
  },
  {
    q: 'Do I need technical skills to use Wally?',
    a: 'Not at all. Wally is designed for non-technical users — content editors, marketing managers, and business owners. Just type what you want in plain English ("update the homepage headline" or "install the SEO plugin") and Wally takes care of the rest.',
  },
  {
    q: 'What is the "Bring your own API key" feature?',
    a: 'BYOK lets you connect your own Anthropic or OpenAI API key so your usage is billed directly by the model provider. This is available on all plans and is a great option if you already have existing API credits.',
  },
  {
    q: 'How does the Free plan differ from paid plans?',
    a: 'The Free plan gives you 50 messages per day on a single site with access to core content management tools. Paid plans unlock unlimited messages, all WordPress admin tools, action logs, priority support, and multi-site management.',
  },
];

export default function FaqPage() {
  return (
    <div className="flex flex-col gap-8">
      <div className="flex flex-col items-center gap-1.5 text-center">
        <h1 className="font-heading text-[28px] font-bold text-foreground">
          Frequently Asked Questions
        </h1>
        <p className="font-sans text-sm text-muted-foreground">
          Everything you need to know about Wally
        </p>
      </div>

      <Accordion.Root type="single" defaultValue={FAQS[0].q} collapsible className="flex flex-col gap-3">
        {FAQS.map((faq) => (
          <Accordion.Item
            key={faq.q}
            value={faq.q}
            className="overflow-hidden rounded-[var(--radius,12px)] border border-border bg-card"
          >
            <Accordion.Header>
              <Accordion.Trigger className="group flex w-full items-center justify-between px-6 py-5 text-left">
                <span className="font-sans text-sm font-semibold leading-[1.5] text-foreground">
                  {faq.q}
                </span>
                <ChevronDown
                  size={18}
                  className="shrink-0 text-muted-foreground transition-transform duration-200 group-data-[state=open]:rotate-180"
                />
              </Accordion.Trigger>
            </Accordion.Header>
            <Accordion.Content className="overflow-hidden data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down">
              <div className="h-px bg-border" />
              <p className="px-6 pb-5 pt-4 font-sans text-[13px] leading-[1.6] text-muted-foreground">
                {faq.a}
              </p>
            </Accordion.Content>
          </Accordion.Item>
        ))}
      </Accordion.Root>
    </div>
  );
}
