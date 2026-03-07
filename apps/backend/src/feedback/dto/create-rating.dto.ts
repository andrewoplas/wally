import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsString, IsNotEmpty, IsOptional, IsIn, MaxLength } from 'class-validator';

export class CreateRatingDto {
  @ApiProperty({ description: 'Message ID being rated' })
  @IsString()
  @IsNotEmpty()
  message_id!: string;

  @ApiProperty({ description: 'Conversation ID' })
  @IsString()
  @IsNotEmpty()
  conversation_id!: string;

  @ApiProperty({ description: 'Rating value' })
  @IsString()
  @IsIn(['thumbs_up', 'thumbs_down'])
  rating!: string;

  @ApiPropertyOptional({ description: 'Optional feedback message', maxLength: 5000 })
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  message?: string;
}
