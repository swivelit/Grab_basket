<!-- Robot walking animation for chatbot -->
<div id="chatbot-robot-animation" style="width:100%;display:flex;justify-content:center;align-items:center;margin-bottom:8px;">
  <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g id="robot-body">
      <rect x="20" y="20" width="20" height="20" rx="6" fill="#c91c1cff" stroke="#232f3e" stroke-width="2"/>
      <rect x="27" y="15" width="6" height="8" rx="3" fill="#fff" stroke="#232f3e" stroke-width="1"/>
      <circle cx="27" cy="30" r="2" fill="#fff"/>
      <circle cx="33" cy="30" r="2" fill="#fff"/>
      <rect x="25" y="40" width="3" height="10" rx="1.5" fill="#232f3e">
        <animate attributeName="y" values="40;48;40" dur="1s" repeatCount="indefinite"/>
      </rect>
      <rect x="32" y="40" width="3" height="10" rx="1.5" fill="#232f3e">
        <animate attributeName="y" values="40;48;40" dur="1s" begin="0.5s" repeatCount="indefinite"/>
      </rect>
    </g>
    <g id="robot-arms">
      <rect x="16" y="28" width="6" height="3" rx="1.5" fill="#232f3e">
        <animate attributeName="x" values="16;12;16" dur="1s" repeatCount="indefinite"/>
      </rect>
      <rect x="38" y="28" width="6" height="3" rx="1.5" fill="#232f3e">
        <animate attributeName="x" values="38;42;38" dur="1s" repeatCount="indefinite"/>
      </rect>
    </g>
  </svg>
</div>
