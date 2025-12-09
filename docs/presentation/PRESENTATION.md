# Stripe CLI Demo Plugin - Team Presentation

**Duration:** ~5 minutes
**Audience:** Developers, Support Agents, Designers

---

## Slide 1: Title

### Testing Stripe Webhooks Locally
**Introducing the Stripe CLI Demo Plugin**

---

## Slide 2: The Problem

### Why This Matters

- Stripe sends webhooks to confirm payments actually happened
- Webhooks go to a public URL — but your laptop isn't public
- Without webhooks, you're flying blind during development
- "It worked in Stripe Checkout" ≠ "The payment was processed"

**Script:**
> "When a customer pays through Stripe, how do we know it actually worked? Stripe sends webhook events to our server. But here's the problem — during local development, Stripe can't reach your laptop. That's where the Stripe CLI comes in."

---

## Slide 3: The Solution

### Stripe CLI Creates a Tunnel

```
Stripe Servers → Stripe CLI (your laptop) → localhost
```

- CLI listens for events from your Stripe account
- Forwards them to your local WordPress site
- No need to deploy to test webhooks

**Script:**
> "The Stripe CLI acts as a tunnel. It listens to your Stripe account and forwards webhook events directly to localhost. You can test the full payment flow without deploying anything."

---

## Slide 4: What You Need

### Requirements

| Item | Details |
|------|---------|
| Stripe CLI | `brew install stripe/stripe-cli/stripe` |
| Stripe Account | Test mode keys (pk_test_, sk_test_) |
| WordPress | Local development site |
| This Plugin | Handles the demo + webhook endpoint |

**Script:**
> "Here's what you need: the Stripe CLI installed on your machine, a Stripe account with test mode keys, a local WordPress site, and this plugin which gives you everything else — the webhook endpoint, a test store, and an event viewer."

---

## Slide 5: The Setup Wizard

### 4-Step Configuration

1. **API Keys** — Enter your test publishable and secret keys
2. **Stripe CLI** — Copy/paste the listen command
3. **Webhook Secret** — Grab the whsec_ from your terminal
4. **Test** — Verify the connection works

**Script:**
> "When you activate the plugin, a setup wizard walks you through everything. Enter your API keys, start the CLI with the provided command, paste in the webhook secret, and test the connection. Takes about 2 minutes."

---

## Slide 6: Live Demo Flow

### How It Works

1. Start the CLI listener in terminal
2. Click "Buy Now" on the $1 demo product
3. Pay with test card `4242 4242 4242 4242`
4. Watch webhook events appear in real-time

**Script:**
> "Let me show you the flow. I start the CLI listener, go to the demo store, buy the $1 widget with a test card, and immediately see the webhook events coming in — payment_intent.created, charge.succeeded, checkout.session.completed. All hitting my local WordPress."

---

## Slide 7: Debugging Webhooks

### Two CLI Modes

**Standard:**
```bash
stripe listen --forward-to yoursite.local/wp-json/stripe-cli-demo/v1/webhook
```

**Debug (JSON output):**
```bash
stripe listen --forward-to yoursite.local/wp-json/stripe-cli-demo/v1/webhook --format JSON
```

**Script:**
> "For debugging, add the --format JSON flag. This shows you the full payload of every event — super helpful when you're building webhook handlers and need to see exactly what data Stripe sends."

---

## Slide 8: For Support & QA

### Testing Without Code

- Reproduce customer webhook issues locally
- Verify webhook handling before releases
- Trigger specific events on demand:

```bash
stripe trigger payment_intent.succeeded
stripe trigger checkout.session.completed
stripe trigger customer.subscription.created
```

**Script:**
> "Support and QA — you can use this too. If a customer reports a webhook issue, you can reproduce it locally. You can also trigger specific events without making real purchases. Just run stripe trigger with the event type."

---

## Slide 9: Key Takeaways

### Remember

1. **Same Account** — CLI and API keys must match
2. **Test Keys Only** — Never use live keys locally
3. **Secret Changes** — Webhook secret resets when CLI restarts
4. **Keep CLI Running** — No tunnel = no webhooks

**Script:**
> "Four things to remember: Make sure your CLI and API keys are from the same Stripe account. Only use test keys. The webhook secret changes every time you restart the CLI, so update it in settings. And keep the CLI running while you test — no tunnel means no webhooks."

---

## Slide 10: Get Started

### Resources

- **Plugin:** `wp-content/plugins/stripe-cli-demo/`
- **GitHub:** github.com/caseproof/stripe-cli-demo
- **Stripe CLI Docs:** stripe.com/docs/stripe-cli
- **Test Cards:** stripe.com/docs/testing

**Questions?**

**Script:**
> "The plugin is ready to use. Clone it from GitHub, run composer install, activate it, and follow the wizard. The README has all the details. Any questions?"

---

# Speaker Notes

## Before the Presentation
- Have terminal open with `stripe listen` ready to run
- Have WordPress admin open to the plugin
- Have Stripe test dashboard open in a tab

## Demo Tips
- Use `--format JSON` during live demo so output is readable
- Test card: `4242 4242 4242 4242`, any future date, any CVC
- Show the Webhook Events page refreshing in real-time

## Common Questions

**Q: Does this work with Local by Flywheel?**
A: Yes, just use your Local site URL in the forward-to command.

**Q: Can I test subscription webhooks?**
A: Yes! Use `stripe trigger customer.subscription.created` etc.

**Q: Why did my webhook secret stop working?**
A: It resets every time you restart `stripe listen`. Copy the new one.

**Q: Do I need to set up webhooks in Stripe Dashboard?**
A: Not for local testing. The CLI handles it. Only for production.
