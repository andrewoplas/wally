import { Logger, ValidationPipe } from '@nestjs/common';
import { NestFactory } from '@nestjs/core';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import { AppModule } from './app/app.module.js';

async function bootstrap() {
  const app = await NestFactory.create(AppModule, {
    // Disable NestJS default logger; we use WallyLoggerService for structured JSON output
    logger: ['error', 'warn', 'log'],
    bodyParser: true,
  });

  const globalPrefix = 'api';
  app.setGlobalPrefix(globalPrefix, {
    // Health check is served without /api prefix for load-balancer compatibility
    exclude: ['health'],
  });

  // Allow WordPress plugin + dev tools to reach the API
  app.enableCors({
    origin:
      process.env['NODE_ENV'] === 'production'
        ? false // tightened in production via env/proxy
        : true,
    methods: ['GET', 'POST', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'X-Site-ID', 'X-API-Key'],
  });

  // Global DTO validation — strip unknown fields, transform primitives
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: false,
      transform: true,
    }),
  );

  // Swagger API docs — only in non-production
  if (process.env['NODE_ENV'] !== 'production') {
    const config = new DocumentBuilder()
      .setTitle('Wally API')
      .setDescription('AI-powered WordPress admin assistant API')
      .setVersion('1.0')
      .addApiKey({ type: 'apiKey', in: 'header', name: 'X-API-Key' }, 'X-API-Key')
      .addApiKey({ type: 'apiKey', in: 'header', name: 'X-Site-ID' }, 'X-Site-ID')
      .build();
    const document = SwaggerModule.createDocument(app, config);
    SwaggerModule.setup('api/docs', app, document);
  }

  const port = process.env['PORT'] ?? 3000;
  await app.listen(port);

  Logger.log(
    `Application is running on: http://localhost:${port}/${globalPrefix}`,
    'Bootstrap',
  );
}

bootstrap();
