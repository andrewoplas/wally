import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import {
  IsString,
  IsOptional,
  IsArray,
  IsNotEmpty,
  ArrayMaxSize,
  ArrayMinSize,
  IsBoolean,
} from 'class-validator';

export class ToolResultItemDto {
  @ApiProperty({ description: 'ID of the tool call from the LLM response' })
  @IsString()
  @IsNotEmpty()
  tool_call_id!: string;

  @ApiProperty({ description: 'Name of the WordPress tool that was executed' })
  @IsString()
  @IsNotEmpty()
  tool_name!: string;

  @ApiProperty({ description: 'Result payload from the tool executor', type: 'object' })
  result!: unknown;

  @ApiPropertyOptional({ description: 'Set to true if the tool execution failed' })
  @IsOptional()
  @IsBoolean()
  is_error?: boolean;
}

export class ToolResultRequestDto {
  @ApiProperty({ description: 'LLM model identifier' })
  @IsString()
  @IsNotEmpty()
  model!: string;

  @ApiProperty({ type: [ToolResultItemDto], description: 'One or more tool execution results' })
  @IsArray()
  @ArrayMinSize(1)
  @ArrayMaxSize(20)
  tool_results!: ToolResultItemDto[];

  @ApiPropertyOptional({ type: 'array', items: { type: 'object' }, maxItems: 100 })
  @IsOptional()
  @IsArray()
  @ArrayMaxSize(100)
  conversation_history?: unknown[];

  @ApiPropertyOptional({ type: 'array', items: { type: 'object' } })
  @IsOptional()
  pending_tool_calls?: unknown[];

  @ApiPropertyOptional({ type: 'object' })
  @IsOptional()
  site_profile?: unknown;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  custom_system_prompt?: string;
}
