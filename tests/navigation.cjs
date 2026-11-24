const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const script = readFileSync(require('node:path').join(__dirname, '../js/script.js'), 'utf8');
function element() {
  const classes = new Set();
  return {
    handlers: {},
    attributes: {},
    classList: {
      toggle(c, force) { if (force) classes.add(c); else classes.delete(c); },
      contains(c) { return classes.has(c); },
    },
    setAttribute(key, value) { this.attributes[key] = value; },
    addEventListener(type, fn) { this.handlers[type] = fn; },
  };
}
for (const signedIn of [false, true]) {
  const navbar = element(), menu = element(), user = element(), account = element(), window = element();
  const elements = { '.header .flex .navbar': navbar, '#menu-btn': menu, '#user-btn': signedIn ? user : null, '.header .flex .account-box': signedIn ? account : null };
  vm.runInNewContext(script, { document: { querySelector: s => elements[s] }, window });
  menu.handlers.click(); assert(navbar.classList.contains('active'));
  assert.equal(menu.attributes['aria-expanded'], 'true');
  if (signedIn) {
    user.handlers.click();
    assert(account.classList.contains('active'));
    assert(!navbar.classList.contains('active'));
    assert.equal(user.attributes['aria-expanded'], 'true');
    assert.equal(menu.attributes['aria-expanded'], 'false');
  }
  window.handlers.scroll(); assert(!navbar.classList.contains('active')); assert(!account.classList.contains('active'));
  assert.equal(menu.attributes['aria-expanded'], 'false');
  if (signedIn) assert.equal(user.attributes['aria-expanded'], 'false');
}
vm.runInNewContext(script, { document: { querySelector: () => null }, window: element() });
console.log('Guest, signed-in and missing-header navigation checks passed');
