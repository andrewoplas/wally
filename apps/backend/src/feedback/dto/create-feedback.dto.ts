import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsString, IsNotEmpty, IsOptional, IsIn, MaxLength } from 'class-validator';

export class CreateFeedbackDto {
  @ApiProperty({ description: 'Feedback message', maxLength: 5000 })
  @IsString()
  @IsNotEmpty()
  @MaxLength(5000)
  message!: string;

  @ApiPropertyOptional({ description: 'Feedback category' })
  @IsOptional()
  @IsString()
  @IsIn(['bug', 'feature', 'general'])
  category?: string;

  @ApiPropertyOptional({ description: 'Contact email' })
  @IsOptional()
  @IsString()
  @MaxLength(320)
  email?: string;

  @ApiPropertyOptional({ description: 'Submitter name' })
  @IsOptional()
  @IsString()
  @MaxLength(200)
  name?: string;
}
