import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsString, IsNotEmpty } from 'class-validator';

export class ValidateLicenseDto {
  @ApiProperty({ description: 'License key to validate' })
  @IsString()
  @IsNotEmpty()
  license_key!: string;
}

export class LicenseFeaturesDto {
  @ApiProperty()
  max_messages_per_day!: number;

  @ApiProperty({ type: [String] })
  models_available!: string[];

  @ApiProperty({ type: [String] })
  tool_categories!: string[];
}

export class LicenseResponseDto {
  @ApiProperty()
  valid!: boolean;

  @ApiProperty()
  tier!: string;

  @ApiProperty({ type: LicenseFeaturesDto })
  features!: LicenseFeaturesDto;

  @ApiPropertyOptional({ nullable: true })
  expires_at!: string | null;

  @ApiPropertyOptional()
  site_count?: number;

  @ApiPropertyOptional()
  max_sites?: number;
}
