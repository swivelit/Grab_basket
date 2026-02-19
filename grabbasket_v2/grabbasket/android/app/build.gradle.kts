plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

// ✅ Meta App Events values
// Set these in android/gradle.properties (or ~/.gradle/gradle.properties) as:
// FACEBOOK_APP_ID=1234567890
// FACEBOOK_CLIENT_TOKEN=abcdef...
val facebookAppId: String = (project.findProperty("FACEBOOK_APP_ID") as String?) ?: "YOUR_FB_APP_ID"
val facebookClientToken: String = (project.findProperty("FACEBOOK_CLIENT_TOKEN") as String?) ?: "YOUR_FB_CLIENT_TOKEN"

android {
    namespace = "com.example.grabbasket"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.example.grabbasket"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName

        // ✅ Meta SDK (Facebook App Events) resources
        resValue("string", "facebook_app_id", facebookAppId)
        resValue("string", "facebook_client_token", facebookClientToken)
        resValue("string", "fb_login_protocol_scheme", "fb$facebookAppId")
    }

    // ✅ Grabbasket flavors (Customer/Seller/Partner)
    flavorDimensions += "app"
    productFlavors {
        create("customer") {
            dimension = "app"
            applicationIdSuffix = ".customer"
            resValue("string", "app_name", "Grabbasket")
        }
        create("seller") {
            dimension = "app"
            applicationIdSuffix = ".seller"
            resValue("string", "app_name", "Grabbasket Seller")
        }
        create("partner") {
            dimension = "app"
            applicationIdSuffix = ".partner"
            resValue("string", "app_name", "Grabbasket Partner")
        }
    }

    buildTypes {
        release {
            // TODO: Add your own signing config for the release build.
            // Signing with the debug keys for now, so `flutter run --release` works.
            signingConfig = signingConfigs.getByName("debug")
        }
    }
}

flutter {
    source = "../.."
}
