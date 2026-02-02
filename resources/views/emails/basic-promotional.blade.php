<h1>{{ $title ?? 'Promotional Email' }}</h1>
<p>Hi {{ $user->name ?? 'Customer' }}!</p>
<p>{{ $message ?? 'We have great deals for you!' }}</p>
<p>Visit our website to shop now!</p>
<p>Thank you,<br>GRAB BASKETS Team</p>