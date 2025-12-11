# Stripe CLI Demo Plugin - Team Presentation

**Duration:** ~8-10 minutes
**Audience:** Developers, Support Agents, Designers
**Version:** 1.2.0

---

## Slide 1: Title

### Testing Stripe Webhooks Locally
**Introducing the Stripe CLI Demo Plugin**

![2025-12-10_18-13-02](https://github.com/user-attachments/assets/410214ff-ac8f-4667-90a3-14231ccc6960)

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

## Slide 4: What's in the Plugin

### Two Ways to Test

| Feature | Purpose |
|---------|---------|
| **Demo Store** | Simple $1 product with Stripe Checkout |
| **MemberPress Integration** | Monitor real MemberPress transactions |
| **Webhook Events** | Real-time event viewer with auto-refresh |
| **Setup Wizard** | Guided configuration in 4 steps |

![2025-12-10_18-13-02](https://github.com/user-attachments/assets/b54694e2-607e-4dc2-94c0-947a768109e1)

![2025-12-10_18-16-51](https://github.com/user-attachments/assets/69a27ff0-64c9-4d6a-baff-8e52335ed6a7)

**Script:**
> "This plugin gives you two ways to test. The Demo Store is a simple $1 product that triggers Stripe Checkout — perfect for learning. If you're already using MemberPress, the plugin also monitors those transactions. Everything shows up in the Webhook Events section."

---

## Slide 5: Installation

### Easy Download from GitHub

1. Go to the **Releases** page on GitHub
2. Download `stripe-cli-demo.zip`
3. Upload to WordPress (Plugins → Add New → Upload)
4. Activate and follow the wizard

**No command line needed!**

![2025-12-10_18-17-54](https://github.com/user-attachments/assets/3f942d7e-2502-481e-9ef3-920da90c4c82)

**Script:**
> "Installing is simple. Download the ZIP from GitHub Releases; it includes everything prebuilt. Upload it to WordPress as you would any other plugin. The wizard handles the rest."

---

## Slide 6: The Setup Wizard

### 4-Step Configuration

1. **API Keys** — Enter your test publishable and secret keys
2. **Stripe CLI** — Copy/paste the listen command
3. **Webhook Secret** — Grab the whsec_ from your terminal
4. **Test** — Verify the connection works

![2025-12-10_18-19-01](https://github.com/user-attachments/assets/2fb81423-889d-483d-9c3e-13055bc94836)
![2025-12-10_18-21-12](https://github.com/user-attachments/assets/bf0b6131-5703-40e3-ad8d-12eb0dcdb7cf)
![2025-12-10_18-21-37](https://github.com/user-attachments/assets/68a58565-c3b3-4319-9eff-8acd59cd26df)

**Script:**
> "When you activate the plugin, a setup wizard walks you through everything. Enter your API keys, start the CLI with the provided command, paste in the webhook secret, and test the connection. Takes about 2 minutes."

---

## Slide 7: Demo Store

### Test Purchases in Seconds

![2025-12-10_18-23-36](https://github.com/user-attachments/assets/59f17c8e-69ea-465a-ac73-74d2762485a2)

- **$1 Demo Widget** — Click "Buy Now" to test
- **Test Card Numbers** — Reference table right on the page
- **CLI Commands** — Copy buttons for both modes
- **Webhook Events** — Auto-refreshes every 10 seconds

**Script:**
> "The Demo Store page has everything in one place. The product card, test card numbers, CLI commands with copy buttons, and the webhook events section at the bottom. Make a purchase and watch the events appear automatically."

---

## Slide 8: Live Demo Flow

### How It Works

1. Start the CLI listener in terminal
2. Click "Buy Now" on the $1 demo product
3. Pay with test card `4242 4242 4242 4242`
4. Watch webhook events appear in real-time

![2025-12-10_18-24-25](https://github.com/user-attachments/assets/e38e15c8-6ec2-4e0c-a593-a02ea85c40d8)

**Script:**
> "Let me show you the flow. I start the CLI listener, go to the demo store, buy the $1 widget with a test card, and immediately see the webhook events coming in — payment_intent.created, charge.succeeded, checkout.session.completed. All hitting my local WordPress."

---

## Slide 9: MemberPress Integration

### Monitor Real Membership Transactions

![2025-12-10_18-26-45](https://github.com/user-attachments/assets/8059b2d7-5f6c-4272-b9ed-d659eac3ecac)

- Automatically detects MemberPress + Stripe Gateway
- Hooks into MemberPress transaction events
- Shows membership purchases, subscription renewals, cancellations
- Separate event log from Demo Store

**Script:**
> "If you're working with MemberPress, the plugin automatically detects it. You'll see a MemberPress Events page that shows real transactions — new signups, renewals, cancellations. This is separate from the demo store events, so you can focus on what matters."

---

## Slide 10: Debugging Webhooks

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

## Slide 11: For Support & QA

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

## Slide 12: Key Takeaways

### Remember

1. **Same Account** — CLI and API keys must match
2. **Test Keys Only** — Never use live keys locally
3. **Secret Changes** — Webhook secret resets when CLI restarts
4. **Keep CLI Running** — No tunnel = no webhooks

**Script:**
> "Four things to remember: Make sure your CLI and API keys are from the same Stripe account. Only use test keys. The webhook secret changes every time you restart the CLI, so update it in settings. And keep the CLI running while you test — no tunnel means no webhooks."

---

## Slide 13: Get Started

### Resources

- **Download:** GitHub Releases page (`stripe-cli-demo.zip`)
- **GitHub:** github.com/caseproof/stripe-cli-demo
- **Stripe CLI Docs:** stripe.com/docs/stripe-cli
- **Test Cards:** stripe.com/docs/testing

**Questions?**

**Script:**
> "Download the plugin from GitHub Releases — no build step needed. Activate it, follow the wizard, and start testing. The README has all the details. Any questions?"

---

# Speaker Notes

## Before the Presentation
- Have terminal open with `stripe listen` ready to run
- Have WordPress admin open to the Demo Store page
- Have Stripe test dashboard open in a tab
- Screenshots ready if doing slides (see suggestions below)

## Suggested Screenshots

1. **Demo Store page** — Full page showing product, test cards, CLI commands, webhook events
2. **MemberPress Events page** — Showing MemberPress transaction events
3. **Setup Wizard Step 2** — Shows the CLI command to copy
4. **Webhook Events section** — Multiple events displayed
5. **GitHub Releases page** — Showing ZIP download
6. **WordPress Admin Menu** — Showing the 3 menu items

## Demo Tips
- Use `--format JSON` during live demo so output is readable
- Test card: `4242 4242 4242 4242`, any future date, any CVC
- Point out the auto-refresh indicator ("Auto-refreshing every 10s")
- Show the copy buttons work with one click

## Common Questions

**Q: Does this work with Local by Flywheel?**
A: Yes, just use your Local site URL in the forward-to command.

**Q: Can I test subscription webhooks?**
A: Yes! Use `stripe trigger customer.subscription.created` etc.

**Q: Why did my webhook secret stop working?**
A: It resets every time you restart `stripe listen`. Copy the new one.

**Q: Do I need to set up webhooks in Stripe Dashboard?**
A: Not for local testing. The CLI handles it. Only for production.

**Q: What's the difference between Demo Store and MemberPress Events?**
A: Demo Store uses our simple $1 product. MemberPress Events monitors real membership transactions. They're separate event logs.

**Q: Do I need composer to install?**
A: No! Download the ZIP from Releases — it includes everything pre-built.

---

# Changelog

| Version | Changes |
|---------|---------|
| 1.2.0 | Added MemberPress integration, consolidated UI, easy ZIP install |
| 1.0.0 | Initial presentation |
