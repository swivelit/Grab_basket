<!-- Enhanced Floating Chatbot Widget -->
<div id="chatbot-widget" style="position:fixed;bottom:24px;left:24px;z-index:2000;">
  <button id="chatbot-toggle" class="btn btn-primary rounded-circle shadow-lg" style="width:70px;height:70px;font-size:2rem;background:linear-gradient(135deg,#1CA9C9,#20cfcf);color:#fff;border:3px solid #fff;animation:chatbotPulse 2s ease-in-out infinite;">
    <!-- Enhanced Dancing Robot SVG -->
    <svg width="40" height="40" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:auto;">
      <g id="robot-body">
        <rect x="20" y="20" width="20" height="20" rx="6" fill="#fff" stroke="#1CA9C9" stroke-width="2"/>
        <rect x="27" y="15" width="6" height="8" rx="3" fill="#1CA9C9" stroke="#232f3e" stroke-width="1"/>
        <circle cx="25" cy="28" r="2" fill="#1CA9C9"/>
        <circle cx="35" cy="28" r="2" fill="#1CA9C9"/>
        <rect x="27" y="32" width="6" height="2" rx="1" fill="#1CA9C9"/>
        <!-- Dancing legs -->
        <rect x="25" y="40" width="3" height="10" rx="1.5" fill="#1CA9C9">
          <animate attributeName="y" values="40;48;40" dur="0.8s" repeatCount="indefinite"/>
        </rect>
        <rect x="32" y="40" width="3" height="10" rx="1.5" fill="#1CA9C9">
          <animate attributeName="y" values="40;48;40" dur="0.8s" begin="0.4s" repeatCount="indefinite"/>
        </rect>
      </g>
      <g id="robot-arms">
        <rect x="14" y="28" width="8" height="3" rx="1.5" fill="#1CA9C9">
          <animate attributeName="x" values="14;10;14" dur="1s" repeatCount="indefinite"/>
        </rect>
        <rect x="38" y="28" width="8" height="3" rx="1.5" fill="#1CA9C9">
          <animate attributeName="x" values="38;42;38" dur="1s" repeatCount="indefinite"/>
        </rect>
      </g>
      <!-- Head bobbing -->
      <rect x="27" y="12" width="6" height="4" rx="2" fill="#fff" stroke="#1CA9C9" stroke-width="1">
        <animate attributeName="y" values="12;8;12" dur="1.2s" repeatCount="indefinite"/>
      </rect>
    </svg>
  </button>
  <div id="chatbot-box" class="card shadow-lg" style="display:none;width:360px;max-width:90vw;margin-bottom:10px;border-radius:20px;overflow:hidden;">
    <div class="card-header d-flex align-items-center justify-content-between" style="background:linear-gradient(135deg,#1CA9C9,#20cfcf);color:#fff;border-radius:20px 20px 0 0;">
      <span><i class="bi bi-robot me-2"></i> grabbasket Assistant</span>
      <button class="btn btn-sm btn-light" onclick="document.getElementById('chatbot-box').style.display='none';" style="border-radius:50%;"><i class="bi bi-x"></i></button>
    </div>
    <div class="card-body p-3" style="height:280px;overflow-y:auto;background:#f8f9fa;">
      <div id="chatbot-messages" style="font-size:0.9rem;"></div>
    </div>
    <div class="card-footer p-3 d-flex flex-column gap-2" style="background:#fff;">
      <form id="chatbot-form" class="d-flex gap-2 mb-1">
        <input type="text" id="chatbot-input" class="form-control" placeholder="Ask me anything..." autocomplete="off" style="border-radius:25px;">
        <button class="btn btn-primary" type="submit" style="border-radius:50%;width:40px;height:40px;"><i class="bi bi-send"></i></button>
      </form>
      <div class="d-flex gap-2">
        <button id="chatbot-support-btn" class="btn btn-outline-secondary btn-sm flex-grow-1" style="border-radius:20px;"><i class="bi bi-envelope me-1"></i> Contact Support</button>
        <a href="tel:+918300504230" class="btn btn-outline-success btn-sm flex-grow-1" style="border-radius:20px;"><i class="bi bi-telephone me-1"></i> Call Support</a>
      </div>
    </div>
  </div>
</div>

<style>
  @keyframes chatbotPulse {
    0%, 100% { 
      transform: scale(1); 
      box-shadow: 0 4px 15px rgba(28, 169, 201, 0.4);
    }
    50% { 
      transform: scale(1.05); 
      box-shadow: 0 6px 25px rgba(28, 169, 201, 0.6);
    }
  }
  
  @keyframes jumpIn { 
    0% { transform: translateY(60px) scale(0.7); opacity:0; } 
    60% { transform: translateY(-18px) scale(1.1); opacity:1; } 
    80% { transform: translateY(6px) scale(0.95); } 
    100% { transform: none; opacity:1; } 
  }
  
  #chatbot-toggle:hover {
    transform: scale(1.1) !important;
    animation: none;
  }
  
  #chatbot-messages {
    max-height: 240px;
  }
  
  #chatbot-messages div {
    padding: 8px 12px;
    margin: 4px 0;
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
  
  #chatbot-messages .text-end {
    background: linear-gradient(135deg, #1CA9C9, #20cfcf);
    color: #fff;
    margin-left: 20px;
  }
</style>
<script>
(function(){
  // Add jump animation to floating emoji button on load
  window.addEventListener('DOMContentLoaded', function() {
    var emojiBtn = document.querySelector('.floating-menu-btn');
    if(emojiBtn) {
      emojiBtn.style.animation = 'jumpIn 0.7s cubic-bezier(.5,-0.5,.5,1.5)';
      setTimeout(function(){ emojiBtn.style.animation = ''; }, 800);
    }
  });
})();
// Jump animation keyframes
var style = document.createElement('style');
style.innerHTML = `@keyframes jumpIn { 0% { transform: translateY(60px) scale(0.7); opacity:0; } 60% { transform: translateY(-18px) scale(1.1); opacity:1; } 80% { transform: translateY(6px) scale(0.95); } 100% { transform: none; opacity:1; } }`;
document.head.appendChild(style);
(function(){
  const toggle = document.getElementById('chatbot-toggle');
  const box = document.getElementById('chatbot-box');
  const messages = document.getElementById('chatbot-messages');
  // Show welcome message when chat is opened
  let greeted = false;
  toggle.onclick = () => {
    box.style.display = box.style.display==='block' ? 'none' : 'block';
    if(box.style.display==='block' && !greeted) {
      messages.innerHTML += `<div class='mb-2'><b>Bot:</b> Welcome to the grabbasket chatbot! Ask me anything about shopping, selling, or using the website.</div>`;
      messages.scrollTop = messages.scrollHeight;
      greeted = true;
    }
  };
  const form = document.getElementById('chatbot-form');
  const input = document.getElementById('chatbot-input');
  function botReply(msg) {
  let reply = '';
  msg = msg.toLowerCase();
  // Website-wide Q&A logic
  if(msg.includes('order') && msg.includes('cancel')) reply = 'To cancel an order, go to <a href="/orders" target="_blank">Orders</a> &gt; Track and click Cancel. You can only cancel before it is shipped.';
  else if(msg.includes('order') && msg.includes('track')) reply = 'Track your orders from the <a href="/orders" target="_blank">Orders</a> page. You will see status and tracking number if shipped.';
  else if(msg.includes('payment')) reply = 'We support Razorpay, UPI, and cards. For payment issues, <a href="/contact" target="_blank">contact support</a> or check your order status.';
  else if(msg.includes('wishlist')) reply = 'Add products to your wishlist by clicking the heart icon on any product. View your wishlist from the <a href="/wishlist" target="_blank">top menu</a>.';
  else if(msg.includes('bulk upload') || (msg.includes('seller') && msg.includes('upload'))) reply = 'Sellers can upload products in bulk from the <a href="/admin" target="_blank">Admin panel</a> using a CSV template.';
  else if(msg.includes('admin')) reply = 'Admins can manage users, products, orders, and view analytics from the <a href="/admin" target="_blank">Admin dashboard</a>.';
  else if(msg.includes('image') && msg.includes('upload')) reply = 'Sellers can upload multiple images for each product from their <a href="/seller/dashboard" target="_blank">dashboard</a>.';
  else if(msg.includes('category') || msg.includes('subcategory')) reply = 'Products are organized by categories and subcategories. Use the <a href="#categoryMenuModal" data-bs-toggle="modal">Shop</a> or Category menu to browse.';
  else if(msg.includes('notification')) reply = 'You will receive notifications for order updates, offers, and important messages. Click the <i class="bi bi-bell"></i> bell icon to view.';
  else if(msg.includes('login') || msg.includes('register')) reply = 'You can <a href="/login" target="_blank">login</a> or <a href="/register" target="_blank">register</a> using the Login link in the top menu. Both buyers and sellers use the same login.';
  else if(msg.includes('gender') && msg.includes('suggestion')) reply = 'We show personalized product suggestions based on your gender and preferences.';
  else if(msg.includes('cart')) reply = 'Add products to your cart and proceed to <a href="/cart" target="_blank">checkout</a> for payment and delivery.';
  else if(msg.includes('delivery') || msg.includes('shipping')) reply = 'Delivery charges and estimated times are shown on the product and checkout pages.';
  else if(msg.includes('support') || msg.includes('help')) reply = 'You can ask me about orders, payments, uploads, navigation, or <a href="/contact" target="_blank">contact our support team</a> for more help!';
  else if(msg.includes('logout')) reply = 'Click your profile or the Logout button in the menu to log out.';
  else if(msg.includes('how') && msg.includes('use')) reply = 'Use the navigation bar to shop, manage your cart, wishlist, and orders. Sellers and admins have their own dashboards.';
  else if(msg.includes('about') && msg.includes('website')) reply = 'grabbasket is a modern e-commerce platform for buyers and sellers. Shop, sell, and manage everything in one place!';
  else reply = 'Sorry, I am a simple assistant. Try asking about <a href="/orders" target="_blank">orders</a>, <a href="/payments" target="_blank">payments</a>, uploads, navigation, or website features!';
  messages.innerHTML += `<div class='mb-2'><b>Bot:</b> ${reply}</div>`;
  messages.scrollTop = messages.scrollHeight;
  }
  form.onsubmit = function(e){
    e.preventDefault();
    const val = input.value.trim();
    if(!val) return;
    messages.innerHTML += `<div class='mb-2 text-end'><b>You:</b> ${val}</div>`;
    botReply(val);
    input.value = '';
  };
  const supportBtn = document.getElementById('chatbot-support-btn');
  supportBtn.onclick = function() {
    const email = '{{ Auth::check() ? Auth::user()->email : "" }}';
    let question = prompt('Enter your question for support:');
    if(!question) return;
    fetch('/chatbot/support', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        email: email,
        question: question
      })
    }).then(r => r.json()).then(data => {
      messages.innerHTML += `<div class='mb-2'><b>Bot:</b> ${data.success ? 'Your question has been sent to support. You will get a reply by email.' : 'Failed to send. Please try again later.'}</div>`;
      messages.scrollTop = messages.scrollHeight;
    });
  };
})();
</script>
