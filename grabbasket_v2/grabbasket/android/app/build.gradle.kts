plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

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

        // ✅ Meta SDK (Facebook App Events) resources (replace with real values)
        resValue("string", "facebook_app_id", "YOUR_FB_APP_ID")
        resValue("string", "facebook_client_token", "YOUR_FB_CLIENT_TOKEN")
        resValue("string", "fb_login_protocol_scheme", "fbYOUR_FB_APP_ID")
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
