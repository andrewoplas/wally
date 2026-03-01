import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import {
  IsString,
  IsNotEmpty,
  IsOptional,
  IsArray,
  MaxLength,
  ArrayMaxSize,
} from 'class-validator';

export class ChatRequestDto {
  @ApiProperty({ description: 'The user message to send to the AI', maxLength: 10000 })
  @IsString()
  @IsNotEmpty()
  @MaxLength(10_000)
  message!: string;

  @ApiProperty({ description: 'LLM model identifier' })
  @IsString()
  @IsNotEmpty()
  model!: string;

  @ApiPropertyOptional({ type: 'array', items: { type: 'object' }, maxItems: 100 })
  @IsOptional()
  @IsArray()
  @ArrayMaxSize(100)
  conversation_history?: unknown[];

  @ApiPropertyOptional({ type: 'object' })
  @IsOptional()
  site_profile?: unknown;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  custom_system_prompt?: string;
}
