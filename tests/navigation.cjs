const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const script = readFileSync(require('node:path').join(__dirname, '../js/script.js'), 'utf8');
function element() {
  const classes = new Set();
  return { handlers: {}, classList: { toggle(c) { classes.has(c) ? classes.delete(c) : classes.add(c); }, remove(c) { classes.delete(c); }, contains(c) { return classes.has(c); } }, addEventListener(type, fn) { this.handlers[type] = fn; } };
}
for (const signedIn of [false, true]) {
  const navbar = element(), menu = element(), user = element(), account = element(), window = element();
  const elements = { '.header .flex .navbar': navbar, '#menu-btn': menu, '#user-btn': signedIn ? user : null, '.header .flex .account-box': signedIn ? account : null };
  vm.runInNewContext(script, { document: { querySelector: s => elements[s] }, window });
  menu.handlers.click(); assert(navbar.classList.contains('active'));
  if (signedIn) { user.handlers.click(); assert(account.classList.contains('active')); assert(!navbar.classList.contains('active')); }
  window.handlers.scroll(); assert(!navbar.classList.contains('active')); assert(!account.classList.contains('active'));
}
vm.runInNewContext(script, { document: { querySelector: () => null }, window: element() });
console.log('Guest, signed-in and missing-header navigation checks passed');
