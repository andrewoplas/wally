import {
  IsString,
  IsNotEmpty,
  IsOptional,
  IsArray,
  MaxLength,
  ArrayMaxSize,
} from 'class-validator';

export class ChatRequestDto {
  @IsString()
  @IsNotEmpty()
  @MaxLength(10_000)
  message!: string;

  @IsString()
  @IsNotEmpty()
  model!: string;

  @IsOptional()
  @IsArray()
  @ArrayMaxSize(100)
  conversation_history?: unknown[];

  @IsOptional()
  site_profile?: unknown;

  @IsOptional()
  @IsString()
  custom_system_prompt?: string;
}
