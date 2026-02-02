<style>
.back-button {
    position: fixed;
    top: 80px;
    left: 20px;
    z-index: 1040;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
}

.back-button:hover {
    background: #0056b3;
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
}

.back-button i {
    font-size: 1.2rem;
}
</style>

<button type="button" class="back-button" onclick="goBack()" title="Go Back">
    <i class="bi bi-arrow-left"></i>
</button>

<script>
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = "{{ route('home') }}";
    }
}
</script>