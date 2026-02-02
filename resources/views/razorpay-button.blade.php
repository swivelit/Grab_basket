<!DOCTYPE html>
<html>

<head>
    <title>Razorpay Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>

<body style="text-align:center; margin-top:50px;">

    <h2>Select Your Payment</h2>

    <!-- Enter Customer Details -->
    <input type="text" id="name" placeholder="Enter Name"><br><br>
    <input type="email" id="email" placeholder="Enter Email"><br><br>
    <input type="text" id="phone" placeholder="Enter Phone"><br><br>

    <!-- Payment Buttons -->
    <button onclick="startPayment(35000)" style="padding:10px 25px; margin:10px;">Pay ₹35,000</button>
    <button onclick="startPayment(30000)" style="padding:10px 25px; margin:10px;">Pay ₹30,000</button>
    <button onclick="startPayment(40000)" style="padding:10px 25px; margin:10px;">Pay ₹40,000</button>
    <button onclick="startPayment(45000)" style="padding:10px 25px; margin:10px;">Pay ₹45,000</button>

    <button onclick="startPayment(50000)" style="padding:10px 25px; margin:10px;">Pay ₹50,000</button>

    <script>
        function startPayment(amount) {
            let customer = {
                name: document.getElementById("name").value,
                email: document.getElementById("email").value,
                phone: document.getElementById("phone").value,
                amount: amount
            };

            fetch("{{ route('create.order') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify(customer)
                })
                .then(res => res.json())
                .then(data => {
                    var options = {
                        "key": data.key,
                        "amount": data.amount,
                        "currency": "INR",
                        "name": "Payment",
                        "order_id": data.order_id,

                        // Prefill customer data
                        "prefill": {
                            "name": data.customer.name,
                            "email": data.customer.email,
                            "contact": data.customer.phone
                        },

                        "handler": function(response) {
                            response.customer = customer;

                            fetch("{{ route('payment.success') }}", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                    },
                                    body: JSON.stringify(response)
                                })
                                .then(res => res.text())
                                .then(msg => alert(msg));
                        }
                    };

                    var rzp = new Razorpay(options);
                    rzp.open();
                });
        }
    </script>

</body>

</html>