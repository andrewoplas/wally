import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsString, IsNotEmpty, IsOptional } from 'class-validator';
import { LicenseFeaturesDto } from './validate-license.dto.js';

export class ActivateLicenseDto {
  @ApiProperty({ description: 'License key to activate' })
  @IsString()
  @IsNotEmpty()
  license_key!: string;

  @ApiProperty({ description: 'Unique site identifier (md5 of site URL)' })
  @IsString()
  @IsNotEmpty()
  site_id!: string;

  @ApiPropertyOptional({ description: 'Site domain/hostname' })
  @IsString()
  @IsOptional()
  domain?: string;
}

export class ActivateResponseDto {
  @ApiProperty()
  valid!: boolean;

  @ApiPropertyOptional()
  tier?: string;

  @ApiPropertyOptional({ type: LicenseFeaturesDto })
  features?: LicenseFeaturesDto;

  @ApiPropertyOptional({ nullable: true })
  expires_at?: string | null;

  @ApiPropertyOptional()
  site_count?: number;

  @ApiPropertyOptional()
  max_sites?: number;

  @ApiPropertyOptional({
    enum: ['invalid_key', 'license_expired', 'license_cancelled', 'max_sites_reached'],
  })
  error?: 'invalid_key' | 'license_expired' | 'license_cancelled' | 'max_sites_reached';
}
