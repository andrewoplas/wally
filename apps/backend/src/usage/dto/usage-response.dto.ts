import { ApiProperty } from '@nestjs/swagger';

export class UsageResponseDto {
  @ApiProperty()
  site_id!: string;

  @ApiProperty()
  total_input_tokens!: number;

  @ApiProperty()
  total_output_tokens!: number;

  @ApiProperty()
  requests!: number;

  @ApiProperty()
  monthly_input_tokens!: number;

  @ApiProperty()
  monthly_output_tokens!: number;

  @ApiProperty({ description: 'YYYY-MM' })
  month!: string;
}
