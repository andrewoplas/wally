//@ts-check

// eslint-disable-next-line @typescript-eslint/no-var-requires
const { composePlugins, withNx } = require('@nx/next');
// eslint-disable-next-line @typescript-eslint/no-var-requires
const { withSentryConfig } = require('@sentry/nextjs');

/**
 * @type {import('@nx/next/plugins/with-nx').WithNxOptions}
 **/
const nextConfig = {
  nx: {},
};

const composedConfig = composePlugins(withNx)(nextConfig);

module.exports = withSentryConfig(composedConfig, {
  silent: !process.env.CI,
  org: 'andrew-5t',
  project: 'wally-frontend',
});
