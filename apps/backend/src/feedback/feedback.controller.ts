import {
  Controller,
  Post,
  Body,
  Req,
  HttpCode,
  HttpStatus,
  HttpException,
  UseGuards,
} from '@nestjs/common';
import { ApiTags, ApiOperation } from '@nestjs/swagger';
import { Request } from 'express';
import { FeedbackService } from './feedback.service.js';
import { CreateFeedbackDto } from './dto/create-feedback.dto.js';
import { CreateRatingDto } from './dto/create-rating.dto.js';
import { CreateGeneralFeedbackDto } from './dto/create-general-feedback.dto.js';
import { AuthGuard } from '../common/guards/auth.guard.js';

interface AuthenticatedRequest extends Request {
  siteId: string;
}

@ApiTags('feedback')
@Controller('v1/feedback')
export class FeedbackController {
  constructor(private readonly feedbackService: FeedbackService) {}

  @ApiOperation({ summary: 'Submit feedback from website (public)' })
  @Post()
  @HttpCode(HttpStatus.CREATED)
  async submitWebsiteFeedback(@Body() body: CreateFeedbackDto) {
    if (!body.message) {
      throw new HttpException(
        { error: 'bad_request', message: 'Message is required' },
        HttpStatus.BAD_REQUEST,
      );
    }

    return this.feedbackService.submit({
      type: 'general',
      message: body.message,
      category: body.category ?? 'general',
      email: body.email,
      name: body.name,
      source: 'website',
    });
  }

  @ApiOperation({ summary: 'Submit per-message rating from plugin' })
  @UseGuards(AuthGuard)
  @Post('rating')
  @HttpCode(HttpStatus.CREATED)
  async submitRating(
    @Body() body: CreateRatingDto,
    @Req() req: AuthenticatedRequest,
  ) {
    return this.feedbackService.submit({
      type: 'rating',
      rating: body.rating,
      message: body.message,
      message_id: body.message_id,
      conversation_id: body.conversation_id,
      source: 'plugin',
      site_id: req.siteId,
    });
  }

  @ApiOperation({ summary: 'Submit general feedback from plugin' })
  @UseGuards(AuthGuard)
  @Post('general')
  @HttpCode(HttpStatus.CREATED)
  async submitGeneralFeedback(
    @Body() body: CreateGeneralFeedbackDto,
    @Req() req: AuthenticatedRequest,
  ) {
    return this.feedbackService.submit({
      type: 'general',
      message: body.message,
      category: body.category ?? 'general',
      conversation_id: body.conversation_id,
      source: 'plugin',
      site_id: req.siteId,
    });
  }
}
