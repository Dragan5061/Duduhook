const cart = new Map();
const cartList = document.querySelector("[data-cart-list]");
const cartEmpty = document.querySelector("[data-cart-empty]");
const whatsappLink = document.querySelector("[data-whatsapp-link]");
const paypalLink = document.querySelector("[data-paypal-link]");
const callLink = document.querySelector("[data-call-link]");
const emailLink = document.querySelector("[data-email-link]");
const webmailLink = document.querySelector("[data-webmail-link]");
const instagramLink = document.querySelector("[data-instagram-link]");
const copyOrderButton = document.querySelector("[data-copy-order]");
const clearCartButton = document.querySelector("[data-clear-cart]");
const cartTotal = document.querySelector("[data-cart-total]");
const phoneNumber = "491738803093";
const contactEmail = "dudu13@hotmail.de";
const instagramUrl = "https://www.instagram.com/dudu_hook/";
const pricePerBag = 95;
let productStatuses = {};

function formatPrice(value) {
  return value.toLocaleString("de-DE", {
    style: "currency",
    currency: "EUR",
  });
}

function getCartLines() {
  return Array.from(cart.keys()).map((name) => {
    return `${name} à ${formatPrice(pricePerBag)} inkl. MwSt. und Versand nach Deutschland`;
  });
}

function buildMessage() {
  const lines = getCartLines().join("\n- ");
  return `Hallo Duduhook,

ich interessiere mich für diese Tasche(n):
- ${lines}

Zwischensumme Taschen: ${formatPrice(getCartTotal())} inkl. MwSt. und Versand nach Deutschland.

Ich möchte per PayPal sofort kaufen oder per Überweisung nach Bestellbestätigung bezahlen.
Bitte bestätigt mir die Verfügbarkeit.

Name:
Adresse:
E-Mail:
Telefon:`;
}

function getCartTotal() {
  return cart.size * pricePerBag;
}

function buildEmailLink(message) {
  const subject = `Duduhook Bestellung ${formatPrice(getCartTotal())}`;
  return `mailto:${contactEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(message)}`;
}

function buildWebmailLink(message) {
  const subject = `Duduhook Bestellung ${formatPrice(getCartTotal())}`;
  const params = new URLSearchParams({
    to: contactEmail,
    subject,
    body: message,
  });

  return `https://outlook.live.com/mail/0/deeplink/compose?${params.toString()}`;
}

function setLinkDisabled(link, disabled) {
  if (!link) {
    return;
  }

  link.classList.toggle("disabled", disabled);

  if (disabled) {
    link.setAttribute("aria-disabled", "true");
    return;
  }

  link.removeAttribute("aria-disabled");
}

function setButtonDisabled(button, disabled) {
  if (!button) {
    return;
  }

  button.disabled = disabled;
  button.classList.toggle("disabled", disabled);
}

function getProductName(card) {
  return card.querySelector("[data-product]")?.dataset.product || "";
}

function getSavedProductStatus(card) {
  return productStatuses[getProductName(card)];
}

function isSoldOut(card) {
  const savedStatus = getSavedProductStatus(card);

  if (savedStatus) {
    return savedStatus === "sold-out";
  }

  return card.dataset.status === "sold-out";
}

function syncProductCard(card) {
  const button = card.querySelector("[data-product]");
  let badge = card.querySelector(".stock-badge");
  const soldOut = isSoldOut(card);
  const name = getProductName(card);

  if (!badge) {
    badge = document.createElement("span");
    badge.className = "stock-badge";
    card.prepend(badge);
  }

  card.classList.toggle("sold-out", soldOut);
  card.dataset.status = soldOut ? "sold-out" : "available";

  if (badge) {
    badge.textContent = soldOut ? "Verkauft" : "Einzelstück";
  }

  if (!button) {
    return;
  }

  if (soldOut) {
    cart.delete(name);
    button.disabled = true;
    button.textContent = "Verkauft";
    return;
  }

  button.disabled = cart.has(name);
  button.textContent = cart.has(name) ? "Im Warenkorb" : "Hinzufügen";
}

async function copyOrderText(text) {
  if (navigator.clipboard && window.isSecureContext) {
    await navigator.clipboard.writeText(text);
    return;
  }

  const textarea = document.createElement("textarea");
  textarea.value = text;
  textarea.setAttribute("readonly", "");
  textarea.style.position = "fixed";
  textarea.style.left = "-9999px";
  document.body.append(textarea);
  textarea.select();

  const copied = document.execCommand("copy");
  textarea.remove();

  if (!copied) {
    throw new Error("Copy failed");
  }
}

function updateCart() {
  const lines = getCartLines();
  const hasItems = lines.length > 0;

  cartEmpty.hidden = hasItems;
  cartTotal.hidden = !hasItems;
  cartList.innerHTML = "";

  for (const name of cart.keys()) {
    const item = document.createElement("li");
    item.innerHTML = `
      <span>${name}</span>
      <div class="cart-item-actions">
        <button type="button" data-remove="${name}" aria-label="${name} entfernen">×</button>
      </div>
      <small>Einzelstück · ${formatPrice(pricePerBag)} inkl. MwSt. und Versand nach Deutschland</small>
    `;
    cartList.append(item);
  }

  cartTotal.querySelector("strong").textContent = `${formatPrice(getCartTotal())} inkl. MwSt. und Versand nach Deutschland`;

  if (!hasItems) {
    if (whatsappLink) {
      whatsappLink.href = "#shop";
    }
    if (paypalLink) {
      paypalLink.href = "#shop";
    }
    if (emailLink) {
      emailLink.href = "#shop";
    }
    if (webmailLink) {
      webmailLink.href = "#shop";
    }
    if (instagramLink) {
      instagramLink.href = instagramUrl;
    }

    setLinkDisabled(whatsappLink, true);
    setLinkDisabled(paypalLink, true);
    setLinkDisabled(emailLink, true);
    setLinkDisabled(webmailLink, true);
    setLinkDisabled(instagramLink, true);
    setLinkDisabled(callLink, true);
    setButtonDisabled(copyOrderButton, true);
    return;
  }

  const message = buildMessage();

  if (whatsappLink) {
    whatsappLink.href = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
  }
  if (paypalLink) {
    paypalLink.href = `https://paypal.me/duduhook/${getCartTotal()}`;
  }
  if (emailLink) {
    emailLink.href = buildEmailLink(message);
  }
  if (webmailLink) {
    webmailLink.href = buildWebmailLink(message);
  }
  if (instagramLink) {
    instagramLink.href = instagramUrl;
  }

  setLinkDisabled(whatsappLink, false);
  setLinkDisabled(paypalLink, false);
  setLinkDisabled(emailLink, false);
  setLinkDisabled(webmailLink, false);
  setLinkDisabled(instagramLink, false);
  setLinkDisabled(callLink, false);
  setButtonDisabled(copyOrderButton, false);
}

document.querySelectorAll("[data-product-card]").forEach((card) => {
  syncProductCard(card);
});

async function loadProductStatuses() {
  try {
    const response = await fetch(`product-status.json?v=${Date.now()}`, {
      cache: "no-store",
    });

    if (!response.ok) {
      return;
    }

    productStatuses = await response.json();
    document.querySelectorAll("[data-product-card]").forEach((card) => {
      syncProductCard(card);
    });
    updateCart();
  } catch {
    productStatuses = {};
  }
}

document.querySelectorAll("[data-product]").forEach((button) => {
  button.addEventListener("click", () => {
    const name = button.dataset.product;
    if (button.disabled || cart.has(name)) {
      return;
    }

    cart.set(name, 1);
    button.disabled = true;
    button.textContent = "Im Warenkorb";
    updateCart();
  });
});

document.querySelectorAll("[data-gallery-image]").forEach((button) => {
  button.addEventListener("click", () => {
    const card = button.closest("[data-product-card]");
    const image = card?.querySelector("[data-product-image]");
    const imagePath = button.dataset.galleryImage;

    if (!image || !imagePath) {
      return;
    }

    image.src = imagePath;
    card.querySelectorAll("[data-gallery-image]").forEach((item) => {
      item.classList.toggle("is-active", item === button);
    });
  });
});

cartList.addEventListener("click", (event) => {
  const target = event.target;
  if (!(target instanceof HTMLButtonElement)) {
    return;
  }

  const removeName = target.dataset.remove;

  if (removeName) {
    cart.delete(removeName);
    const button = document.querySelector(`[data-product="${CSS.escape(removeName)}"]`);
    if (button && !button.closest(".sold-out")) {
      button.disabled = false;
      button.textContent = "Hinzufügen";
    }
  }

  updateCart();
});

clearCartButton.addEventListener("click", () => {
  cart.clear();
  document.querySelectorAll("[data-product]").forEach((button) => {
    if (!button.closest(".sold-out")) {
      button.disabled = false;
      button.textContent = "Hinzufügen";
    }
  });
  updateCart();
});

if (copyOrderButton) {
  copyOrderButton.addEventListener("click", async () => {
    if (cart.size === 0) {
      return;
    }

    const originalText = copyOrderButton.textContent || "Bestelltext kopieren";

    try {
      await copyOrderText(buildMessage());
      copyOrderButton.textContent = "Bestelltext kopiert";
    } catch {
      copyOrderButton.textContent = "Bitte manuell kopieren";
    }

    window.setTimeout(() => {
      copyOrderButton.textContent = originalText;
    }, 2200);
  });
}

updateCart();
loadProductStatuses();
