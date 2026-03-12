/**
 * ToolCallbackController
 *
 * POST /v1/tool-callback
 *
 * After the WP plugin executes a tool locally, it sends the result here.
 * This controller resolves or rejects the awaiting Promise in ToolCallbackStore,
 * which unblocks the MCP tool handler in the Agent SDK loop.
 */

import {
  Controller,
  Post,
  Body,
  HttpCode,
  HttpStatus,
  NotFoundException,
  UseGuards,
} from '@nestjs/common';
import { ApiTags, ApiExcludeEndpoint } from '@nestjs/swagger';
import { AuthGuard } from '../common/guards/auth.guard.js';
import { ToolCallbackStore } from './tool-callback.store.js';

interface ToolCallbackBody {
  call_id: string;
  result: {
    success: boolean;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    data?: any;
    error?: string;
  };
}

@ApiTags('agent')
@Controller('v1/tool-callback')
@UseGuards(AuthGuard)
export class ToolCallbackController {
  constructor(private readonly callbackStore: ToolCallbackStore) {}

  @ApiExcludeEndpoint()
  @Post()
  @HttpCode(HttpStatus.OK)
  toolCallback(@Body() body: ToolCallbackBody): { ok: boolean } {
    const { call_id, result } = body;

    if (result.success) {
      const found = this.callbackStore.resolve(call_id, result);
      if (!found) {
        throw new NotFoundException(`No pending callback for call_id: ${call_id}`);
      }
    } else {
      const error = result.error ?? 'Tool execution failed';
      const found = this.callbackStore.reject(call_id, error);
      if (!found) {
        throw new NotFoundException(`No pending callback for call_id: ${call_id}`);
      }
    }

    return { ok: true };
  }
}
