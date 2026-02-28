/**
 * Classifies the user's message intent to determine which WordPress knowledge
 * chunks should be injected into the prompt.
 *
 * Returns an array of intent keys that map to knowledge files.
 * Multiple intents can be returned for cross-domain queries.
 * Intent keys map 1:1 to knowledge file names (without .md extension).
 */
import { Injectable } from '@nestjs/common';

// ─── Types ────────────────────────────────────────────────────────────────────

export type IntentKey = string;

/** A conversation message — either a plain string or an object with a content field. */
export type ConversationMessage = string | { content?: string; [key: string]: unknown };

interface IntentPattern {
  key: IntentKey;
  patterns: RegExp[];
}

// ─── Pattern Definitions ──────────────────────────────────────────────────────

const INTENT_PATTERNS: IntentPattern[] = [
  // ── Core Content & Structure ──────────────────────────────────────────────
  {
    key: 'elementor',
    patterns: [
      /elementor/i,
      /page\s*builder/i,
      /widget(?:s)?\s*(?:type|setting|content|tree|structure)/i,
      /section(?:s)?\s*(?:and|\/)\s*column/i,
      /\b(?:hero|banner|slider|landing)\s*(?:section|page|block)/i,
      /_elementor_data/i,
    ],
  },
  {
    key: 'content',
    patterns: [
      /\b(?:post|page|draft|publish|excerpt|featured\s*image)\b/i,
      /\b(?:create|update|edit|delete|trash|restore)\s+(?:a\s+)?(?:post|page|article|blog)/i,
      /post[\s_]?(?:type|status|meta|content|title)/i,
      /\bWP_Query\b/i,
      /\bcustom\s*post\s*type/i,
      /\bCPT\b/,
      /\bcase[\s-]?stud(?:y|ies)\b/i,
      /\bebook/i,
      /\bwebinar/i,
      /\binfographic/i,
      /\bnews[\s-]?press\b/i,
      /\bfan[\s-]?love\b/i,
    ],
  },
  {
    key: 'acf',
    patterns: [
      /\bACF\b/,
      /\badvanced\s*custom\s*fields?\b/i,
      /\bcustom\s*field/i,
      /\bfield\s*group/i,
      /\brepeater\s*field/i,
      /\bflexible\s*content/i,
      /\bget_field\b/i,
      /\bupdate_field\b/i,
    ],
  },
  {
    key: 'plugins',
    patterns: [
      /\b(?:install|activate|deactivate|update|delete|remove)\b.*\bplugins?\b/i,
      /\bplugins?\s*(?:management|list|status|version|update|install)/i,
      /\blist\s+(?:all\s+)?plugins\b/i,
      /\bplugins?\b/i,
    ],
  },
  {
    key: 'menus',
    patterns: [
      /\bmenu(?:s)?\b/i,
      /\bnavigation\b/i,
      /\bnav\s*(?:bar|item|link)/i,
      /\bheader\s*(?:link|menu|navigation)/i,
      /\bfooter\s*(?:link|menu|navigation)/i,
    ],
  },
  {
    key: 'media',
    patterns: [
      /\b(?:media|image|photo|picture|upload|attachment|gallery)\b/i,
      /\bfeatured\s*image/i,
      /\bthumbnail\b/i,
      /\b(?:jpg|jpeg|png|gif|svg|webp)\b/i,
      /\bfile\s*(?:upload|size|type)/i,
    ],
  },
  {
    key: 'settings',
    patterns: [
      /\b(?:site|blog)\s*(?:title|name|description|tagline|url)/i,
      /\bget_option\b/i,
      /\bupdate_option\b/i,
      /\btimezone\b/i,
      /\bgeneral\s*settings/i,
      /\breading\s*settings/i,
      /\bwriting\s*settings/i,
      /\bfront\s*page\s*display/i,
    ],
  },
  {
    key: 'users',
    patterns: [
      /\buser(?:s)?\s+(?:list|role|permission|account|profile|meta)/i,
      /\brole(?:s)?\b/i,
      /\bpermission(?:s)?\b/i,
      /\bcapabilit(?:y|ies)\b/i,
      /\badmin(?:istrator)?s?\b/i,
      /\beditors?\b/i,
      /\bauthors?\b/i,
      /\bcontributors?\b/i,
      /\bsubscribers?\b/i,
      /\bcurrent_user_can\b/i,
    ],
  },
  {
    key: 'taxonomies',
    patterns: [
      /\bcategor(?:y|ies)\b/i,
      /\btag(?:s)?\b/i,
      /\btaxonom(?:y|ies)\b/i,
      /\bterm(?:s)?\b/i,
    ],
  },
  {
    key: 'theme',
    patterns: [
      /\btheme\s+(?:mod|setting|option|customiz)/i,
      /\btemplate\s*(?:hierarchy|file|part)/i,
      /\bcustomizer\b/i,
      /\bwidget\s*area/i,
      /\benqueue\s*(?:script|style)/i,
      /\bchild\s*theme\b/i,
    ],
  },
  {
    key: 'search',
    patterns: [
      /\b(?:search|find|replace|swap|change)\s+(?:text|content|string|word|phrase)/i,
      /\bfind\s+and\s+replace/i,
      /\bsearch\s+(?:for|across|through|in)\b/i,
      /\breplace\s+(?:all|every|each)\b/i,
    ],
  },

  // ── E-commerce ────────────────────────────────────────────────────────────
  {
    key: 'woocommerce',
    patterns: [
      /\bwoocommerce\b/i,
      /\bwc_get_(?:product|order|customer)/i,
      /\bWC\(\)/,
      /\bproducts?\s*(?:type|variation|stock|sku|price|categor)/i,
      /\borders?\s*(?:status|total|item|billing|shipping|note)/i,
      /\bcart\s*(?:item|total|add|remove|empty|coupon)/i,
      /\bcheckout\s*(?:field|process|page)/i,
      /\bsku\b/i,
      /\b(?:shop|store)\s*(?:page|setting|currency)/i,
      /\bproducts?\b.*\bprice\b/i,
      /\borders?\b.*\bstatus\b/i,
      /\bcart\b.*\btotal\b/i,
    ],
  },
  {
    key: 'ecommerce-plugins',
    patterns: [
      /\b(?:easy\s*)?digital\s*downloads?\b/i,
      /\bEDD\b/,
      /\bmemberpress\b/i,
      /\blearnDash\b/i,
      /\blifterLMS\b/i,
      /\bmembership\s*(?:plan|level|rule)/i,
      /\bcourse(?:s)?\s+(?:lesson|topic|quiz|module|progress)/i,
      /\blms\b/i,
    ],
  },

  // ── SEO ───────────────────────────────────────────────────────────────────
  {
    key: 'yoast-seo',
    patterns: [
      /\byoast\b/i,
      /\bwpseo\b/i,
      /\b_yoast_wpseo/i,
      /\byoast\s*(?:seo|breadcrumb|sitemap|schema)/i,
    ],
  },
  {
    key: 'rank-math',
    patterns: [/\brank\s*math\b/i, /\brank_math_/i],
  },

  // ── Forms ─────────────────────────────────────────────────────────────────
  {
    key: 'gravity-forms',
    patterns: [/\bgravity\s*forms?\b/i, /\bGFAPI\b/, /\bgform_/i],
  },
  {
    key: 'contact-form-7',
    patterns: [/\bcontact\s*form\s*7\b/i, /\bCF7\b/, /\bwpcf7\b/i],
  },
  {
    key: 'forms-general',
    patterns: [
      /\bwpforms\b/i,
      /\bninja\s*forms?\b/i,
      /\bfluent\s*forms?\b/i,
      /\bcontact\s*form\b/i,
      /\bform\s*(?:submission|entry|entries|builder|field)/i,
    ],
  },

  // ── Caching & Performance ─────────────────────────────────────────────────
  {
    key: 'caching-plugins',
    patterns: [
      /\bwp\s*rocket\b/i,
      /\bw3\s*total\s*cache\b/i,
      /\bwp\s*super\s*cache\b/i,
      /\blitespeed\s*cache\b/i,
      /\bwp\s*fastest\s*cache\b/i,
      /\bautoptimize\b/i,
      /\bwp[\s-]*optimize\b/i,
      /\bperfmatters\b/i,
      /\b(?:clear|purge|flush)\s*(?:the\s+)?cache\b/i,
      /\bcache\s*(?:plugin|purge|clear|flush|bust)/i,
      /\bpage\s*cache\b/i,
      /\bobject\s*cache\b/i,
      /\bredis\b/i,
      /\bmemcached?\b/i,
    ],
  },

  // ── Security ──────────────────────────────────────────────────────────────
  {
    key: 'security-plugins',
    patterns: [
      /\bwordfence\b/i,
      /\bsucuri\b/i,
      /\b(?:ithemes|solid)\s*security\b/i,
      /\bfirewall\s*(?:rule|setting|block)/i,
      /\bmalware\s*scan\b/i,
      /\bbrute\s*force\b/i,
      /\blogin\s*(?:attempt|limit|lockout|protect)/i,
      /\btwo[\s-]*factor\s*auth/i,
      /\b2FA\b/,
      /\bip\s*(?:block|ban|whitelist|blacklist)/i,
      /\bblock\s+(?:an?\s+)?ip/i,
    ],
  },

  // ── Backup ────────────────────────────────────────────────────────────────
  {
    key: 'backup-plugins',
    patterns: [
      /\bupdraftplus\b/i,
      /\bbackwpup\b/i,
      /\bduplicator\b/i,
      /\bbackup\s*(?:plugin|schedule|restore|site|database|file)/i,
      /\brestore\s+(?:from\s+)?backup/i,
      /\bmigrat(?:e|ion)\b/i,
    ],
  },

  // ── Page Builders (non-Elementor) ─────────────────────────────────────────
  {
    key: 'page-builders',
    patterns: [
      /\bbeaver\s*builder\b/i,
      /\bdivi\b/i,
      /\bbrizy\b/i,
      /\boxygen\s*builder\b/i,
      /\bwpbakery\b/i,
      /\bvisual\s*composer\b/i,
      /\bvc_row\b/i,
      /\bjs_composer\b/i,
      /\bet_pb_/i,
      /\b_fl_builder/i,
    ],
  },

  // ── Multilingual ──────────────────────────────────────────────────────────
  {
    key: 'multilingual-plugins',
    patterns: [
      /\bWPML\b/,
      /\bpolylang\b/i,
      /\btranslatepress\b/i,
      /\btranslat(?:e|ion)\s*(?:plugin|page|post|content|string)/i,
      /\bmultilingual\b/i,
      /\blanguage\s*(?:switch|selector|version)/i,
    ],
  },

  // ── Email ─────────────────────────────────────────────────────────────────
  {
    key: 'wp-mail-smtp',
    patterns: [
      /\bwp\s*mail\s*smtp\b/i,
      /\bsmtp\b/i,
      /\bemail\s*(?:deliverability|delivery|sending|config|setup|log)/i,
      /\bwp_mail\b/i,
      /\bmail\s*(?:not\s+)?(?:sending|working|delivered)/i,
      /\bemails?\s+(?:are\s+)?not\s+(?:sending|working|delivered)/i,
      /\bemails?\s+(?:deliverability|delivery|sending|config|log)/i,
      /\b(?:sendgrid|mailgun|postmark|sparkpost|ses)\b/i,
    ],
  },

  // ── Analytics ─────────────────────────────────────────────────────────────
  {
    key: 'analytics-plugins',
    patterns: [
      /\bsite\s*kit\b/i,
      /\bgoogle\s*(?:analytics|search\s*console|adsense|tag\s*manager)/i,
      /\bmonsterinsights\b/i,
      /\bGTM\b/,
      /\btag\s*manager\b/i,
      /\bpixelyoursite\b/i,
      /\bfacebook\s*pixel\b/i,
      /\banalytics\s*(?:track|code|ID|dashboard|report)/i,
      /\btracking\s*(?:code|pixel|script|tag)/i,
      /\bGA4?\b/,
      /\bpageview\s*tracking/i,
    ],
  },

  // ── Jetpack ───────────────────────────────────────────────────────────────
  {
    key: 'jetpack',
    patterns: [
      /\bjetpack\b/i,
      /\bwordpress\.com\s*connect/i,
      /\bpublicize\b/i,
      /\bphoton\s*(?:cdn|image)/i,
      /\bjetpack\s*(?:module|stat|protect|search|sso|monitor)/i,
    ],
  },

  // ── Redirection ───────────────────────────────────────────────────────────
  {
    key: 'redirection',
    patterns: [
      /\bredirect(?:ion|s)?\s*(?:plugin|rule|url|301|302|log|monitor)/i,
      /\b301\s*redirect/i,
      /\b302\s*redirect/i,
      /\bbroken\s*link\s*checker/i,
      /\burl\s*redirect/i,
      /\b404\s*(?:log|monitor|error|redirect)/i,
    ],
  },

  // ── Other SEO ─────────────────────────────────────────────────────────────
  {
    key: 'seo-other',
    patterns: [
      /\ball\s*in\s*one\s*seo\b/i,
      /\baioseo\b/i,
      /\bseopress\b/i,
      /\b_seopress_/i,
      /\baioseo_/i,
    ],
  },

  // ── GDPR/Compliance ───────────────────────────────────────────────────────
  {
    key: 'gdpr-compliance',
    patterns: [
      /\bcookie\s*(?:consent|banner|notice|law|popup|bar)/i,
      /\bgdpr\b/i,
      /\bccpa\b/i,
      /\bcookieyes\b/i,
      /\bcomplianz\b/i,
      /\bcookie\s*notice\b/i,
      /\bconsent\s*(?:mode|management|banner)/i,
      /\bprivacy\s*(?:policy|compliance|law|regulation)/i,
    ],
  },

  // ── Events ────────────────────────────────────────────────────────────────
  {
    key: 'events-plugins',
    patterns: [
      /\bevents?\s*calendar\b/i,
      /\btribe_events\b/i,
      /\bevent(?:s)?\s*(?:list|create|manage|venue|organizer|ticket)/i,
      /\bupcoming\s*events?\b/i,
      /\bthe\s*events\s*calendar\b/i,
      /\btribe\b/i,
    ],
  },

  // ── Image Optimization ────────────────────────────────────────────────────
  {
    key: 'image-optimization',
    patterns: [
      /\bsmush\b/i,
      /\bewww\b/i,
      /\bshortpixel\b/i,
      /\bimage\s*optimi[sz]/i,
      /\bcompress\s*image/i,
      /\bwebp\s*conver/i,
      /\bavif\b/i,
      /\blazy\s*load\s*image/i,
      /\bbulk\s*(?:smush|optimize|compress)\b/i,
    ],
  },

  // ── Email Marketing & Popups ──────────────────────────────────────────────
  {
    key: 'email-marketing',
    patterns: [
      /\bmailchimp\b/i,
      /\bmc4wp\b/i,
      /\boptinmonster\b/i,
      /\bpopup\s*maker\b/i,
      /\bpopup(?:s)?\s*(?:create|trigger|display|exit\s*intent)/i,
      /\bexit\s*intent\b/i,
      /\bnewsletter\s*(?:signup|subscription|form|popup)/i,
      /\bemail\s*(?:list|subscriber|optin|opt[\s-]in|marketing|campaign)/i,
      /\blead\s*(?:capture|generation|magnet)/i,
    ],
  },

  // ── Elementor Addons ──────────────────────────────────────────────────────
  {
    key: 'elementor-addons',
    patterns: [
      /\bessential\s*addons?\b/i,
      /\belementskit\b/i,
      /\bheader[\s-]*footer\s*(?:elementor|builder)\b/i,
      /\bpremium\s*addons?\s*(?:for\s*)?elementor\b/i,
      /\bultimate\s*addons?\s*(?:for\s*)?elementor\b/i,
      /\bEAEL\b/,
      /\bHFE\b/,
    ],
  },

  // ── Slider Plugins ────────────────────────────────────────────────────────
  {
    key: 'slider-plugins',
    patterns: [
      /\bslider\s*revolution\b/i,
      /\brevslider\b/i,
      /\brev_slider\b/i,
      /\bmetaslider\b/i,
      /\bsmart\s*slider\b/i,
      /\bslider\s*(?:plugin|create|shortcode|layer)/i,
    ],
  },

  // ── Gutenberg Addons ──────────────────────────────────────────────────────
  {
    key: 'gutenberg-addons',
    patterns: [
      /\bkadence\s*blocks?\b/i,
      /\bspectra\s*(?:blocks?|gutenberg)?\b/i,
      /\bgenerateblocks\b/i,
      /\bstarter\s*templates?\b/i,
      /\buagb\b/i,
      /\bkadence\/\w/i,
      /\buagb\/\w/i,
      /\bgenerateblocks\/\w/i,
    ],
  },

  // ── Theme Extensions ──────────────────────────────────────────────────────
  {
    key: 'theme-extensions',
    patterns: [
      /\bastra\s*pro\b/i,
      /\bastra\s*addon\b/i,
      /\bgeneratepress\s*(?:pro|premium)\b/i,
      /\bkadence\s*pro\b/i,
      /\bgp[\s-]*(?:premium|elements)\b/i,
      /\bastra[\s-]*(?:settings|module|layout|hook)/i,
    ],
  },

  // ── WooCommerce Extensions ────────────────────────────────────────────────
  {
    key: 'woocommerce-extensions',
    patterns: [
      /\bwoopayments?\b/i,
      /\bwoo(?:commerce)?\s*(?:stripe|paypal)\b/i,
      /\bstripe\s*(?:gateway|payment|checkout)/i,
      /\bpaypal\s*(?:gateway|payment|checkout)/i,
      /\bpayment\s*gateway/i,
    ],
  },

  // ── Social Plugins ────────────────────────────────────────────────────────
  {
    key: 'social-plugins',
    patterns: [
      /\bsmash\s*balloon\b/i,
      /\binstagram\s*feed\b/i,
      /\baddtoany\b/i,
      /\bshare\s*button/i,
      /\bsocial\s*(?:share|feed|photo|media\s*feed|button)/i,
      /\btrustindex\b/i,
      /\breview\s*(?:widget|aggregat|badge|display)/i,
    ],
  },

  // ── Content Plugins ───────────────────────────────────────────────────────
  {
    key: 'content-plugins',
    patterns: [
      /\btablepress\b/i,
      /\bwp\s*popular\s*posts\b/i,
      /\bpopular\s*posts?\s*(?:widget|list|display)/i,
      /\btable\s*(?:plugin|shortcode|display|import|export)/i,
      /\b\[table\s/i,
    ],
  },

  // ── Dev Utility Plugins ───────────────────────────────────────────────────
  {
    key: 'dev-utility-plugins',
    patterns: [
      /\bcode\s*snippets?\b/i,
      /\bsearch\s*regex\b/i,
      /\bwp[\s-]*rollback\b/i,
      /\bwp[\s-]*pagenavi\b/i,
      /\bfont[\s-]*awesome\b/i,
      /\bphp\s*snippet/i,
      /\bpagination\s*(?:plugin|setting|style)/i,
    ],
  },

  // ── Audit Logging ─────────────────────────────────────────────────────────
  {
    key: 'audit-logging',
    patterns: [
      /\bsimple\s*history\b/i,
      /\bactivity\s*log\b/i,
      /\baudit\s*(?:log|trail|history)/i,
      /\buser\s*activity\s*(?:log|track|monitor)/i,
      /\bchange\s*log\s*(?:plugin|track)/i,
    ],
  },

  // ── Media Plugins ─────────────────────────────────────────────────────────
  {
    key: 'media-plugins',
    patterns: [
      /\bregenerate\s*thumbnails?\b/i,
      /\bfilebird\b/i,
      /\bmedia\s*folder/i,
      /\bimage\s*(?:size|thumbnail)\s*(?:regenerat|rebuild)/i,
      /\bmedia\s*(?:library\s*)?(?:organiz|folder|categor)/i,
    ],
  },

  // ── Hosting Plugins ───────────────────────────────────────────────────────
  {
    key: 'hosting-plugins',
    patterns: [
      /\bwp\s*engine\b/i,
      /\bwpe\s*sign\s*on\b/i,
      /\bhostinger\b/i,
      /\bwpmu\s*dev\b/i,
      /\bwp\s*abilities\b/i,
      /\bhosting\s*(?:plugin|tool|dashboard|management)/i,
      /\bserver\s*(?:info|environment|php\s*version)/i,
    ],
  },

  // ── Niche Utility Plugins ─────────────────────────────────────────────────
  {
    key: 'niche-utility-plugins',
    patterns: [
      /\bnps\s*survey\b/i,
      /\bnet\s*promoter\b/i,
      /\bwc\s*admin\s*email\b/i,
      /\bci\s*hub\b/i,
      /\bkb\s*(?:vector|custom\s*svg)\b/i,
      /\bzipwp\b/i,
      /\botgs\s*installer\b/i,
      /\bhello\s*elementor\b/i,
      /\bobject[\s-]*cache\s*(?:plugin|pro|drop)/i,
      /\bsvg\s*(?:upload|support|sanitiz)/i,
      /\bdam\s*(?:integration|connect|asset)/i,
    ],
  },

  // ── WordPress Core APIs ───────────────────────────────────────────────────
  {
    key: 'wp-enqueue',
    patterns: [
      /\bwp_enqueue_(?:script|style)\b/i,
      /\bwp_register_(?:script|style)\b/i,
      /\bwp_dequeue_(?:script|style)\b/i,
      /\bwp_localize_script\b/i,
      /\bwp_add_inline_(?:script|style)\b/i,
      /\benqueue\b.*\b(?:script|style|asset|css|js)\b/i,
      /\bscript\s*(?:depend|version|async|defer|module)/i,
    ],
  },
  {
    key: 'wp-ajax',
    patterns: [
      /\bwp_ajax_/i,
      /\badmin[\s-]*ajax\b/i,
      /\bwp_send_json/i,
      /\bajax\s*(?:request|handler|action|endpoint|call)/i,
      /\bheartbeat\s*(?:api|send|received|tick)/i,
      /\bwp_doing_ajax\b/i,
    ],
  },
  {
    key: 'wp-http',
    patterns: [
      /\bwp_remote_(?:get|post|request|head)\b/i,
      /\bwp_safe_remote_/i,
      /\bwp_remote_retrieve_/i,
      /\bhttp\s*(?:api|request|client)/i,
      /\bremote\s*(?:request|fetch|call|api)/i,
      /\bdownload_url\b/i,
    ],
  },
  {
    key: 'wp-i18n',
    patterns: [
      /\b(?:__|_e|_n|_x|_nx|_ex)\s*\(/i,
      /\bload_(?:plugin|theme)_textdomain\b/i,
      /\btext[\s-]*domain\b/i,
      /\btranslat(?:e|ion)\s*(?:function|string|file|ready)/i,
      /\bi18n\b/i,
      /\b(?:pot|po|mo)\s*file/i,
      /\bgettext\b/i,
      /\bwp_set_script_translations\b/i,
    ],
  },
  {
    key: 'wp-conditionals',
    patterns: [
      /\bis_single\b/i,
      /\bis_page\b/i,
      /\bis_archive\b/i,
      /\bis_admin\b/i,
      /\bis_front_page\b/i,
      /\bis_home\b/i,
      /\bis_singular\b/i,
      /\bconditional\s*tag/i,
      /\bwp_is_mobile\b/i,
      /\bis_user_logged_in\b/i,
    ],
  },
  {
    key: 'wp-theme-json',
    patterns: [
      /\btheme\.json\b/i,
      /\btheme[\s_]*json\b/i,
      /\bWP_Theme_JSON\b/i,
      /\bglobal\s*styles?\b/i,
      /\bblock\s*theme\s*(?:config|setting|style)/i,
      /\b--wp--preset--/i,
    ],
  },
  {
    key: 'wp-rest-api',
    patterns: [
      /\bREST\s*API\b/i,
      /\bregister_rest_route\b/i,
      /\bWP_REST_/i,
      /\b\/wp-json\//i,
      /\b\/wp\/v2\//i,
      /\bapi\s*endpoint/i,
    ],
  },
  {
    key: 'wp-hooks',
    patterns: [
      /\badd_action\b/i,
      /\badd_filter\b/i,
      /\bdo_action\b/i,
      /\bapply_filters\b/i,
      /\bremove_action\b/i,
      /\bremove_filter\b/i,
      /\bhook(?:s)?\s+(?:system|list|reference|priority)/i,
      /\baction(?:s)?\s+(?:and|&)\s+filters?\b/i,
    ],
  },
  {
    key: 'wp-database',
    patterns: [
      /\bwpdb\b/i,
      /\b\$wpdb\b/,
      /\bdbDelta\b/i,
      /\bcustom\s*(?:database\s*)?table/i,
      /\bSQL\s*query/i,
      /\bdatabase\s*(?:table|query|migration|schema)/i,
    ],
  },
  {
    key: 'wp-cron',
    patterns: [
      /\bcron\s*(?:job|event|schedule|task)/i,
      /\bwp_schedule_event\b/i,
      /\bwp_cron\b/i,
      /\bscheduled\s*(?:event|task|action)/i,
      /\bDISABLE_WP_CRON\b/i,
    ],
  },
  {
    key: 'wp-security',
    patterns: [
      /\bnonce\b/i,
      /\bwp_nonce/i,
      /\bsanitiz(?:e|ation)\b/i,
      /\besc_html\b/i,
      /\besc_attr\b/i,
      /\besc_url\b/i,
      /\bwp_kses/i,
      /\bdata\s*(?:sanitiz|validat|escap)/i,
      /\binput\s*validat/i,
      /\bXSS\b/,
      /\bSQL\s*injection/i,
    ],
  },
  {
    key: 'wp-transients',
    patterns: [
      /\btransient(?:s)?\b/i,
      /\bget_transient\b/i,
      /\bset_transient\b/i,
      /\bwp_cache_(?:get|set|delete|flush)\b/i,
      /\bobject\s*cach(?:e|ing)\b/i,
    ],
  },
  {
    key: 'wp-gutenberg',
    patterns: [
      /\bgutenberg\b/i,
      /\bblock\s*(?:editor|type|pattern|template|category)/i,
      /\breusable\s*block/i,
      /\bwp:(?:paragraph|heading|image|group|column)/i,
      /\bparse_blocks\b/i,
      /\bregister_block_type\b/i,
      /\bfull\s*site\s*edit/i,
      /\bFSE\b/,
    ],
  },
  {
    key: 'wp-multisite',
    patterns: [
      /\bmultisite\b/i,
      /\bnetwork\s*(?:admin|site|setting|activated)/i,
      /\bswitch_to_blog\b/i,
      /\bsub[\s-]?site/i,
      /\bsuper\s*admin/i,
      /\bMULTISITE\b/,
    ],
  },
  {
    key: 'wp-shortcodes',
    patterns: [
      /\bshortcode(?:s)?\b/i,
      /\badd_shortcode\b/i,
      /\bdo_shortcode\b/i,
      /\[[\w_]+\b/,
    ],
  },
  {
    key: 'wp-widgets',
    patterns: [
      /\bwidget(?:s)?\b/i,
      /\bsidebar(?:s)?\b/i,
      /\bregister_sidebar\b/i,
      /\bWP_Widget\b/i,
      /\bdynamic_sidebar\b/i,
    ],
  },
  {
    key: 'wp-comments',
    patterns: [
      /\bcomment(?:s)?\b/i,
      /\bdiscussion\s*settings/i,
      /\bcomment\s*(?:moderat|approv|spam|status|form)/i,
      /\bWP_Comment_Query\b/i,
      /\bakismet\b/i,
    ],
  },
  {
    key: 'wp-rewrite',
    patterns: [
      /\bpermalink\s*(?:structure|setting|pattern)/i,
      /\brewrite\s*(?:rule|tag|flush)/i,
      /\badd_rewrite_rule\b/i,
      /\bflush_rewrite_rules\b/i,
      /\bquery_var/i,
      /\bslug\s+(?:change|update|modify)/i,
    ],
  },
];

/** Maximum number of intents returned per classification (controls token budget). */
const MAX_INTENTS = 4;

/** Number of recent conversation messages to check for context continuity. */
const CONTEXT_WINDOW = 2;

// ─── Service ──────────────────────────────────────────────────────────────────

@Injectable()
export class IntentClassifierService {
  /**
   * Classify a user message into intent keys.
   *
   * Checks the current message first, then the last `CONTEXT_WINDOW` messages
   * from conversation history to maintain intent continuity (e.g. if the user
   * previously asked about Elementor and now says "change the title", Elementor
   * knowledge is still injected).
   *
   * Always includes `'general'` as a baseline intent.
   * Caps the result at `MAX_INTENTS` (4), prioritising intents matched from the
   * current message over those matched from context.
   */
  classifyIntent(message: string, conversationContext: ConversationMessage[] = []): IntentKey[] {
    const fromMessage = this.matchIntents(message);

    // Check last CONTEXT_WINDOW messages for continuity
    const recentContext = conversationContext.slice(-CONTEXT_WINDOW);
    const fromContext: IntentKey[] = [];
    for (const ctx of recentContext) {
      const text = typeof ctx === 'string' ? ctx : (ctx.content ?? '');
      for (const key of this.matchIntents(text)) {
        if (!fromMessage.has(key) && !fromContext.includes(key)) {
          fromContext.push(key);
        }
      }
    }

    // Merge: current-message intents first, then context intents
    const merged = [...fromMessage, ...fromContext];

    // Always include 'general' as baseline knowledge
    if (!merged.includes('general')) {
      merged.push('general');
    }

    // Cap at MAX_INTENTS — keep 'general' plus the first (MAX_INTENTS - 1) others
    if (merged.length > MAX_INTENTS) {
      const general = merged.filter((k) => k === 'general');
      const others = merged.filter((k) => k !== 'general').slice(0, MAX_INTENTS - 1);
      return [...general, ...others];
    }

    return merged;
  }

  /** Run all patterns against `text` and return the matching intent keys as a Set. */
  private matchIntents(text: string): Set<IntentKey> {
    const matched = new Set<IntentKey>();
    for (const { key, patterns } of INTENT_PATTERNS) {
      for (const pattern of patterns) {
        if (pattern.test(text)) {
          matched.add(key);
          break; // One match per intent is sufficient
        }
      }
    }
    return matched;
  }
}
