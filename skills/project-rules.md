# Rawlabs Website Project Rules

## Project Identity
This project is the static website prototype for Rawlabs, a premium freeze-dried cat and dog food brand.

The website must support:
- Premium brand perception
- Fast loading speed
- Mobile-first responsive design
- SEO-friendly structure
- Clear product discovery
- Smooth cart flow
- Trust-focused e-commerce experience

## Current Project Stack
Use the existing stack unless the user explicitly approves a change.

Current structure:
- HTML
- CSS
- Vanilla JavaScript
- Static product data in JavaScript
- GitHub Pages deployment

Do not add frameworks such as React, Vue, Next.js, Bootstrap, Tailwind, or jQuery unless explicitly approved.

## File Safety Rules
Before editing, inspect the relevant files first.

Do not delete or overwrite:
- Product data
- Cart logic
- Existing working category filters
- Existing product detail routing
- Existing GitHub Pages-compatible links

Avoid large refactors unless the user specifically requests them.

## GitHub Pages Rules
The site is deployed through GitHub Pages.

Preserve relative paths.
Avoid absolute local paths.
Do not use Windows-only paths in code.
Make sure links work under:

https://ugurluali.github.io/rawlabs-website/

## Existing Working Flows
These flows are currently important and should not be broken:

- Home page opens correctly
- Category cards route to the correct pet type
- Cat products filter correctly
- Dog products filter correctly
- Product detail page opens using slug
- Add to cart works
- Cart page displays added products

## Development Rule
When making changes:
1. Explain what will be changed
2. Keep the change small
3. Modify only necessary files
4. Test the affected flow
5. Summarize the result

## Do Not
Never randomly change brand colors.
Never remove SEO tags without replacing them.
Never remove product information.
Never break cart or product detail functionality.
Never introduce heavy dependencies.
