import 'reflect-metadata';
import { MessageBuilderService } from './message-builder.service';

describe('MessageBuilderService', () => {
  let service: MessageBuilderService;

  beforeEach(() => {
    service = new MessageBuilderService();
  });

  describe('buildToolResultMessages', () => {
    const toolResults = [
      {
        tool_call_id: 'call_123',
        tool_name: 'list_posts',
        result: JSON.stringify([{ id: 1, title: 'Hello' }]),
        is_error: false,
      },
    ];

    it('builds messages with assistant tool_use block followed by user tool_result block', () => {
      const messages = service.buildToolResultMessages(null, null, toolResults);

      const assistantMsg = messages.find((m) => m.role === 'assistant');
      const userMsg = messages[messages.length - 1];

      expect(assistantMsg).toBeDefined();
      expect(Array.isArray(assistantMsg!.content)).toBe(true);
      const toolUseBlock = (assistantMsg!.content as Array<Record<string, unknown>>).find(
        (b) => b['type'] === 'tool_use',
      );
      expect(toolUseBlock).toBeDefined();
      expect(toolUseBlock!['name']).toBe('list_posts');

      expect(userMsg.role).toBe('user');
      const resultBlock = (userMsg.content as Array<Record<string, unknown>>).find(
        (b) => b['type'] === 'tool_result',
      );
      expect(resultBlock).toBeDefined();
      expect(resultBlock!['tool_use_id']).toBe('call_123');
    });

    it('includes pending_tool_calls input when provided', () => {
      const pendingToolCalls = [
        { tool_call_id: 'call_123', tool: 'list_posts', input: { post_type: 'post' } },
      ];

      const messages = service.buildToolResultMessages(null, pendingToolCalls, toolResults);
      const assistantMsg = messages.find((m) => m.role === 'assistant');
      const toolUseBlock = (assistantMsg!.content as Array<Record<string, unknown>>).find(
        (b) => b['type'] === 'tool_use',
      );

      expect(toolUseBlock!['input']).toEqual({ post_type: 'post' });
    });

    it('normalises PHP empty-array inputs to empty objects', () => {
      const pendingToolCalls = [
        { tool_call_id: 'call_123', tool: 'list_posts', input: [] },
      ];

      const messages = service.buildToolResultMessages(null, pendingToolCalls, toolResults);
      const assistantMsg = messages.find((m) => m.role === 'assistant');
      const toolUseBlock = (assistantMsg!.content as Array<Record<string, unknown>>).find(
        (b) => b['type'] === 'tool_use',
      );

      expect(toolUseBlock!['input']).toEqual({});
    });

    it('incorporates conversation history messages', () => {
      const history = [
        { role: 'user', content: 'List my posts' },
        { role: 'assistant', content: 'Sure, let me check.' },
      ];

      const messages = service.buildToolResultMessages(history, null, toolResults);
      expect(messages[0]).toEqual({ role: 'user', content: 'List my posts' });
      expect(messages[1]).toEqual({ role: 'assistant', content: 'Sure, let me check.' });
    });

    it('converts tool_result history entries to user messages with content blocks', () => {
      const history = [
        { role: 'tool_result', content: '{"result": "done"}', tool_call_id: 'prev_call' },
      ];

      const messages = service.buildToolResultMessages(history, null, toolResults);
      const toolResultHistoryMsg = messages.find(
        (m) => m.role === 'user' && Array.isArray(m.content) &&
          (m.content as Array<Record<string, unknown>>).some((b) => b['tool_use_id'] === 'prev_call'),
      );
      expect(toolResultHistoryMsg).toBeDefined();
    });

    it('handles error tool results', () => {
      const errorResults = [
        {
          tool_call_id: 'call_err',
          tool_name: 'delete_post',
          result: 'Post not found',
          is_error: true,
        },
      ];

      const messages = service.buildToolResultMessages(null, null, errorResults);
      const userMsg = messages[messages.length - 1];
      const resultBlock = (userMsg.content as Array<Record<string, unknown>>).find(
        (b) => b['type'] === 'tool_result',
      );
      expect(resultBlock!['is_error']).toBe(true);
    });
  });
});
