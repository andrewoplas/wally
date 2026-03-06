# Page Templates — Ready-to-Use Gutenberg Block Markup

Use these complete templates when a user asks to create or build a page. Replace placeholder text (ALL CAPS in brackets) with the user's actual content. Each template is complete and ready to pass as `post_content` to `create_post` or `update_post`.

**Important:** Always customize placeholder text with the user's real business name, tagline, and content. Ask for missing details if needed before building.

---

## 1. Business Landing Page

Sections: Hero → Features → About → Testimonials → CTA → Contact

```html
<!-- wp:cover {"overlayColor":"black","minHeight":500,"isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:500px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-60 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">[BUSINESS NAME]</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc">[TAGLINE — what you do and who you serve]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" href="/contact">Get Started</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#about" style="color:#ffffff">Learn More</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">What We Offer</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief intro to your services — 1-2 sentences]</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">[Service 1]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief description of service 1 — 2-3 sentences explaining the value.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">[Service 2]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief description of service 2 — 2-3 sentences explaining the value.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">[Service 3]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief description of service 3 — 2-3 sentences explaining the value.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"pale-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-cyan-blue-background-color has-background">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:media-text {"mediaPosition":"right","mediaType":"image"} -->
<div class="wp-block-media-text has-media-on-the-right is-stacked-on-mobile"><div class="wp-block-media-text__content">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">About [BUSINESS NAME]</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Tell your story — how you started, your mission, what makes you different. 2-3 paragraphs work well here.]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/about">Our Full Story</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div><figure class="wp-block-media-text__media"><img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=600" alt="[BUSINESS NAME] team at work"/></figure></div>
<!-- /wp:media-text -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">What Our Clients Say</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>"[Testimonial 1 — a specific result or experience the client had.]"</p><cite>— [Name], [Title/Company]</cite></blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>"[Testimonial 2 — a specific result or experience the client had.]"</p><cite>— [Name], [Title/Company]</cite></blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>"[Testimonial 3 — a specific result or experience the client had.]"</p><cite>— [Name], [Title/Company]</cite></blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"vivid-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-vivid-cyan-blue-background-color has-background">
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Ready to Get Started?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffdd"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffdd">[One compelling sentence — the outcome they'll get by contacting you.]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" href="/contact">Contact Us Today</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
```

---

## 2. Restaurant Page

Sections: Hero → Menu Highlights → About → Hours & Location → Reservation CTA

```html
<!-- wp:cover {"url":"https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200","dimRatio":50,"overlayColor":"black","minHeight":600,"isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:600px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-50 has-background-dim"></span><img class="wp-block-cover__image-background" alt="Restaurant interior" src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">[RESTAURANT NAME]</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc">[Cuisine type and atmosphere — e.g., "Authentic Italian Cuisine in the Heart of Downtown"]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"luminous-vivid-amber","textColor":"black"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-luminous-vivid-amber-background-color has-text-color has-background wp-element-button" href="#reservations">Reserve a Table</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" style="color:#ffffff" href="#menu">View Menu</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Our Menu</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief description of your culinary approach or signature dishes.]</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Starters</h3>
<!-- /wp:heading -->
<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td>[Dish Name]</td><td>$[Price]</td></tr><tr><td>[Dish Name]</td><td>$[Price]</td></tr><tr><td>[Dish Name]</td><td>$[Price]</td></tr></tbody></table></figure>
<!-- /wp:table -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Mains</h3>
<!-- /wp:heading -->
<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td>[Dish Name]</td><td>$[Price]</td></tr><tr><td>[Dish Name]</td><td>$[Price]</td></tr><tr><td>[Dish Name]</td><td>$[Price]</td></tr></tbody></table></figure>
<!-- /wp:table -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Desserts</h3>
<!-- /wp:heading -->
<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><tbody><tr><td>[Dish Name]</td><td>$[Price]</td></tr><tr><td>[Dish Name]</td><td>$[Price]</td></tr><tr><td>[Dish Name]</td><td>$[Price]</td></tr></tbody></table></figure>
<!-- /wp:table -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"pale-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-cyan-blue-background-color has-background">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:media-text {"mediaType":"image"} -->
<div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600" alt="[RESTAURANT NAME] interior"/></figure><div class="wp-block-media-text__content">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Our Story</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Tell the restaurant's story — founding, inspiration, culinary philosophy. 2-3 sentences.]</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>[More about the chef or family behind the restaurant, awards, or what makes the food special.]</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:media-text -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Hours &amp; Location</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Hours</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Monday – Friday: [Opening] – [Closing]<br/>Saturday: [Opening] – [Closing]<br/>Sunday: [Opening] – [Closing]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Find Us</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Street Address]<br/>[City, State ZIP]<br/>Phone: [Phone Number]<br/>Email: [Email Address]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"luminous-vivid-amber","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-luminous-vivid-amber-background-color has-background">
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Make a Reservation</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Book your table online or call us at [PHONE NUMBER].</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"black","textColor":"white"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-black-background-color has-text-color has-background wp-element-button" href="[BOOKING URL or /contact]">Book Online</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
```

---

## 3. Portfolio Page

Sections: Hero → Project Grid → About → Skills → Contact CTA

```html
<!-- wp:cover {"overlayColor":"black","minHeight":450,"isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:450px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-80 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">[YOUR NAME]</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc">[Your role — e.g., "Freelance Web Designer &amp; Developer"]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-cyan-blue"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button" href="#work">View My Work</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" style="color:#ffffff" href="/contact">Hire Me</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Selected Work</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="https://images.unsplash.com/photo-1467232004584-a241de8bcf5d?w=600" alt="[Project 1 name]"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[Project 1 Title]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Brief description — type of project, your role, key result.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="https://images.unsplash.com/photo-1522542550221-31fd19575a2d?w=600" alt="[Project 2 name]"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[Project 2 Title]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Brief description — type of project, your role, key result.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?w=600" alt="[Project 3 name]"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[Project 3 Title]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Brief description — type of project, your role, key result.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"cyan-bluish-gray","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-cyan-bluish-gray-background-color has-background">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:media-text {"mediaPosition":"right","mediaType":"image"} -->
<div class="wp-block-media-text has-media-on-the-right is-stacked-on-mobile"><div class="wp-block-media-text__content">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">About Me</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Your bio — background, experience, what drives your work. 2-3 sentences.]</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>[Specializations, notable clients, or what types of projects you love most.]</p>
<!-- /wp:paragraph -->
</div><figure class="wp-block-media-text__media"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400" alt="[YOUR NAME] — [Role]"/></figure></div>
<!-- /wp:media-text -->
<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Skills</h3>
<!-- /wp:heading -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:list -->
<ul class="wp-block-list"><li>[Skill 1]</li><li>[Skill 2]</li><li>[Skill 3]</li></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:list -->
<ul class="wp-block-list"><li>[Skill 4]</li><li>[Skill 5]</li><li>[Skill 6]</li></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"black","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-black-background-color has-background">
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Let's Work Together</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc">Have a project in mind? I'd love to hear about it.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-cyan-blue"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button" href="/contact">Get in Touch</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
```

---

## 4. About Page

Sections: Hero → Story → Team → Values → CTA

```html
<!-- wp:cover {"overlayColor":"black","minHeight":350,"isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:350px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-70 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">About [BUSINESS NAME]</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc">[One-line mission statement]</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:media-text {"mediaType":"image"} -->
<div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="https://images.unsplash.com/photo-1552664730-d307ca884978?w=600" alt="[BUSINESS NAME] founding story"/></figure><div class="wp-block-media-text__content">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Our Story</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[How the business started — founding year, founding story, the problem you set out to solve. 2-3 sentences.]</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>[How you've grown and where you are today. What you're proud of.]</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:media-text -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"pale-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-cyan-blue-background-color has-background">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Meet the Team</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"align":"center","sizeSlug":"medium","className":"is-style-rounded"} -->
<figure class="wp-block-image aligncenter size-medium is-style-rounded"><img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=300" alt="[Team Member 1 Name]"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">[Team Member 1 Name]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><em>[Title/Role]</em></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief bio — background and what they bring to the team.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"align":"center","sizeSlug":"medium","className":"is-style-rounded"} -->
<figure class="wp-block-image aligncenter size-medium is-style-rounded"><img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=300" alt="[Team Member 2 Name]"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">[Team Member 2 Name]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><em>[Title/Role]</em></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief bio — background and what they bring to the team.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:image {"align":"center","sizeSlug":"medium","className":"is-style-rounded"} -->
<figure class="wp-block-image aligncenter size-medium is-style-rounded"><img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=300" alt="[Team Member 3 Name]"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">[Team Member 3 Name]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><em>[Title/Role]</em></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief bio — background and what they bring to the team.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Our Values</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">[Value 1]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[What this value means in practice for your team and clients.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">[Value 2]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[What this value means in practice for your team and clients.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center">[Value 3]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[What this value means in practice for your team and clients.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"vivid-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-vivid-cyan-blue-background-color has-background">
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Work With Us</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffdd"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffdd">[Compelling invitation — what working with you looks like.]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" href="/contact">Get in Touch</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
```

---

## 5. Contact Page

Sections: Intro → Contact Form + Info (2-col) → FAQ

```html
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">Get in Touch</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Brief invitation — e.g., "We'd love to hear from you. Fill out the form below or reach us directly."]</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"width":"60%"} -->
<div class="wp-block-column" style="flex-basis:60%">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Send Us a Message</h2>
<!-- /wp:heading -->
<!-- wp:shortcode -->
[contact-form-7 id="[FORM_ID]" title="Contact form 1"]
<!-- /wp:shortcode -->
</div>
<!-- /wp:column -->
<!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%">
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Contact Info</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><strong>Address</strong><br/>[Street Address]<br/>[City, State ZIP]</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><strong>Phone</strong><br/>[Phone Number]</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><strong>Email</strong><br/>[Email Address]</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><strong>Hours</strong><br/>Monday – Friday: [Hours]<br/>Saturday: [Hours]<br/>Sunday: Closed</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Frequently Asked Questions</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[FAQ Question 1?]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Answer to FAQ question 1.]</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[FAQ Question 2?]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Answer to FAQ question 2.]</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[FAQ Question 3?]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Answer to FAQ question 3.]</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
```

---

## 6. Service Page

Sections: Hero → Services (3-col) → Process → Testimonials → CTA

```html
<!-- wp:cover {"overlayColor":"black","minHeight":400,"isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:400px"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-70 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Our Services</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc">[What you help clients achieve — outcome-focused tagline.]</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[Service 1 Name]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Description of service 1 — what's included, who it's for, and the key benefit.]</p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul class="wp-block-list"><li>[Deliverable or feature]</li><li>[Deliverable or feature]</li><li>[Deliverable or feature]</li></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[Service 2 Name]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Description of service 2 — what's included, who it's for, and the key benefit.]</p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul class="wp-block-list"><li>[Deliverable or feature]</li><li>[Deliverable or feature]</li><li>[Deliverable or feature]</li></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">[Service 3 Name]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>[Description of service 3 — what's included, who it's for, and the key benefit.]</p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul class="wp-block-list"><li>[Deliverable or feature]</li><li>[Deliverable or feature]</li><li>[Deliverable or feature]</li></ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"pale-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-pale-cyan-blue-background-color has-background">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">How It Works</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">1. [Step 1]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[What happens in step 1.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">2. [Step 2]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[What happens in step 2.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">3. [Step 3]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[What happens in step 3.]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Client Results</h2>
<!-- /wp:heading -->
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>"[Specific result testimonial about your service.]"</p><cite>— [Name], [Company]</cite></blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>"[Specific result testimonial about your service.]"</p><cite>— [Name], [Company]</cite></blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"backgroundColor":"vivid-cyan-blue","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-vivid-cyan-blue-background-color has-background">
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff">Ready to Get Started?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffdd"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffdd">[Specific CTA — what they'll get by contacting you today.]</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" href="/contact">Request a Free Consultation</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
```

---

## 7. Coming Soon Page

Sections: Full-screen cover with headline, launch date, email CTA

```html
<!-- wp:cover {"overlayColor":"black","minHeight":100,"minHeightUnit":"vh","isDark":true,"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-dark" style="min-height:100vh"><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-80 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"clamp(2.5rem, 6vw, 5rem)"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff;font-size:clamp(2.5rem, 6vw, 5rem)">[BUSINESS NAME]</h1>
<!-- /wp:heading -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffffcc"}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffffcc">Something exciting is coming.</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff99"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffff99">[Brief description of what you're building — 1-2 sentences to build anticipation.]</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffcc"},"typography":{"fontSize":"1.25rem"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffcc;font-size:1.25rem">Launching [MONTH] [YEAR]</p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"30px"} -->
<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffffaa"}}} -->
<p class="has-text-align-center has-text-color" style="color:#ffffffaa">Want to be notified when we launch? Get in touch:</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-cyan-blue"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button" href="mailto:[EMAIL ADDRESS]">Notify Me</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div></div>
<!-- /wp:cover -->
```

---

## Usage Notes

- Replace all `[BRACKETED PLACEHOLDERS]` with real content from the user
- For contact forms: replace `[FORM_ID]` with the actual Contact Form 7 ID (use `list_comments` or check the CF7 list)
- For images: placeholder URLs use Unsplash — replace with actual uploaded images or use `upload_media_from_url` to import them
- Colors can be customized using WordPress preset color names (see `gutenberg-blocks.md`)
- To add more sections, refer to the patterns in `gutenberg-blocks.md`
- Always set `post_type: "page"` and `post_status: "publish"` (or `"draft"` to preview first) when creating these templates
