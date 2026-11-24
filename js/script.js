// Lấy các phần tử từ DOM
const userBox = document.querySelector(".header .flex .account-box");
const navbar = document.querySelector(".header .flex .navbar");
const userBtn = document.querySelector("#user-btn");
const menuBtn = document.querySelector("#menu-btn");

function setPanelOpen(panel, button, open) {
  panel?.classList.toggle("active", open);
  button?.setAttribute("aria-expanded", String(open));
}

// Định nghĩa sự kiện nhấn cho nút người dùng
userBtn?.addEventListener("click", () => {
  setPanelOpen(userBox, userBtn, !userBox?.classList.contains("active"));
  setPanelOpen(navbar, menuBtn, false);
});

// Định nghĩa sự kiện cuộn trang cho cửa sổ trình duyệt
window.addEventListener("scroll", () => {
  setPanelOpen(userBox, userBtn, false);
  setPanelOpen(navbar, menuBtn, false);
});

// Định nghĩa sự kiện nhấn cho nút trình đơn
menuBtn?.addEventListener("click", () => {
  setPanelOpen(navbar, menuBtn, !navbar?.classList.contains("active"));
  setPanelOpen(userBox, userBtn, false);
});
