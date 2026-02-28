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
  @IsString()
  @IsNotEmpty()
  tool_call_id!: string;

  @IsString()
  @IsNotEmpty()
  tool_name!: string;

  result!: unknown;

  @IsOptional()
  @IsBoolean()
  is_error?: boolean;
}

export class ToolResultRequestDto {
  @IsString()
  @IsNotEmpty()
  model!: string;

  @IsArray()
  @ArrayMinSize(1)
  @ArrayMaxSize(20)
  tool_results!: ToolResultItemDto[];

  @IsOptional()
  @IsArray()
  @ArrayMaxSize(100)
  conversation_history?: unknown[];

  @IsOptional()
  pending_tool_calls?: unknown[];

  @IsOptional()
  site_profile?: unknown;

  @IsOptional()
  @IsString()
  custom_system_prompt?: string;
}
