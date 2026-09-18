package cloud.kasmui.hadits;

import android.app.Activity;
import android.app.DownloadManager;
import android.content.ActivityNotFoundException;
import android.content.Context;
import android.content.Intent;
import android.graphics.Color;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.view.View;
import android.view.Window;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.SslErrorHandler;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.net.http.SslError;
import android.widget.FrameLayout;
import android.widget.ProgressBar;
import android.widget.Toast;

public class MainActivity extends Activity {

    private static final String START_URL = "https://kasmui.cloud/hadits/";
    private static final String ALLOWED_HOST = "kasmui.cloud";

    private WebView webView;
    private ProgressBar progressBar;
    private boolean showingErrorPage = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        requestWindowFeature(Window.FEATURE_NO_TITLE);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            getWindow().setStatusBarColor(Color.rgb(15, 63, 133));
        }

        FrameLayout root = new FrameLayout(this);
        root.setBackgroundColor(Color.WHITE);

        webView = new WebView(this);
        FrameLayout.LayoutParams webParams = new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
        );
        root.addView(webView, webParams);

        progressBar = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        progressBar.setMax(100);
        progressBar.setProgress(0);
        FrameLayout.LayoutParams progressParams = new FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                dp(4)
        );
        progressParams.gravity = android.view.Gravity.TOP;
        root.addView(progressBar, progressParams);

        setContentView(root);
        configureWebView();

        if (savedInstanceState != null) {
            webView.restoreState(savedInstanceState);
        } else {
            openStartPage();
        }
    }

    private void configureWebView() {
        WebSettings s = webView.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setLoadsImagesAutomatically(true);
        s.setUseWideViewPort(true);
        s.setLoadWithOverviewMode(false);
        s.setSupportZoom(true);
        s.setBuiltInZoomControls(true);
        s.setDisplayZoomControls(false);
        s.setCacheMode(WebSettings.LOAD_DEFAULT);
        s.setJavaScriptCanOpenWindowsAutomatically(true);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setUserAgentString(s.getUserAgentString() + " HaditsKasmuiAndroid/1.0.0");

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            s.setMixedContentMode(WebSettings.MIXED_CONTENT_COMPATIBILITY_MODE);
        }

        CookieManager cookies = CookieManager.getInstance();
        cookies.setAcceptCookie(true);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
            cookies.setAcceptThirdPartyCookies(webView, true);
        }

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progressBar.setProgress(newProgress);
                progressBar.setVisibility(newProgress >= 100 ? View.GONE : View.VISIBLE);
            }
        });

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                return handleNavigation(request.getUrl());
            }

            @SuppressWarnings("deprecation")
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                return handleNavigation(Uri.parse(url));
            }

            @Override
            public void onPageStarted(WebView view, String url, android.graphics.Bitmap favicon) {
                if (!url.startsWith("data:") && !url.startsWith("about:blank")) {
                    showingErrorPage = false;
                }
                progressBar.setVisibility(View.VISIBLE);
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                progressBar.setVisibility(View.GONE);
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
                if (request.isForMainFrame()) {
                    String message = Build.VERSION.SDK_INT >= Build.VERSION_CODES.M
                            ? String.valueOf(error.getDescription())
                            : "Halaman tidak dapat dimuat.";
                    showErrorPage("Koneksi atau server bermasalah", message);
                }
            }

            @SuppressWarnings("deprecation")
            @Override
            public void onReceivedError(WebView view, int errorCode, String description, String failingUrl) {
                if (!showingErrorPage) {
                    showErrorPage("Koneksi atau server bermasalah", description);
                }
            }

            @Override
            public void onReceivedSslError(WebView view, SslErrorHandler handler, SslError error) {
                handler.cancel();
                showErrorPage(
                        "Sertifikat HTTPS bermasalah",
                        "Koneksi aman ke kasmui.cloud tidak dapat diverifikasi oleh perangkat ini."
                );
            }
        });

        webView.setDownloadListener(new DownloadListener() {
            @Override
            public void onDownloadStart(String url, String userAgent, String contentDisposition,
                                        String mimetype, long contentLength) {
                if (url == null) return;

                if (url.startsWith("blob:") || url.startsWith("data:")) {
                    Toast.makeText(
                            MainActivity.this,
                            "Unduhan ini dibuat oleh halaman. Jika tidak tersimpan, gunakan menu cetak/simpan PDF dari halaman.",
                            Toast.LENGTH_LONG
                    ).show();
                    return;
                }

                try {
                    DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
                    request.setMimeType(mimetype);
                    request.addRequestHeader("User-Agent", userAgent);
                    String cookie = CookieManager.getInstance().getCookie(url);
                    if (cookie != null) request.addRequestHeader("Cookie", cookie);
                    request.setNotificationVisibility(
                            DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED
                    );
                    request.setTitle("Hadits Online");
                    request.setDescription("Mengunduh file…");
                    request.setDestinationInExternalPublicDir(
                            Environment.DIRECTORY_DOWNLOADS,
                            guessFileName(contentDisposition, mimetype)
                    );

                    DownloadManager manager =
                            (DownloadManager) getSystemService(Context.DOWNLOAD_SERVICE);
                    manager.enqueue(request);
                    Toast.makeText(
                            MainActivity.this,
                            "Unduhan dimulai. Periksa folder Download.",
                            Toast.LENGTH_SHORT
                    ).show();
                } catch (Exception e) {
                    openExternal(Uri.parse(url));
                }
            }
        });
    }

    private String guessFileName(String contentDisposition, String mimetype) {
        String name = "hadits-download";
        if (contentDisposition != null) {
            int idx = contentDisposition.toLowerCase().indexOf("filename=");
            if (idx >= 0) {
                name = contentDisposition.substring(idx + 9)
                        .replace("\"", "")
                        .trim();
            }
        }
        if (!name.contains(".") && mimetype != null) {
            if (mimetype.contains("pdf")) name += ".pdf";
            else if (mimetype.contains("json")) name += ".json";
            else if (mimetype.contains("text")) name += ".txt";
        }
        return name;
    }

    private boolean handleNavigation(Uri uri) {
        if (uri == null) return false;

        String scheme = uri.getScheme() == null ? "" : uri.getScheme().toLowerCase();

        if ("haditsapp".equals(scheme) && "retry".equals(uri.getHost())) {
            openStartPage();
            return true;
        }

        if ("http".equals(scheme) || "https".equals(scheme)) {
            String host = uri.getHost();
            if (host != null && (host.equals(ALLOWED_HOST) || host.endsWith("." + ALLOWED_HOST))) {
                return false;
            }
            openExternal(uri);
            return true;
        }

        if ("intent".equals(scheme)) {
            try {
                Intent intent = Intent.parseUri(uri.toString(), Intent.URI_INTENT_SCHEME);
                startActivity(intent);
            } catch (Exception e) {
                Toast.makeText(this, "Aplikasi tujuan tidak tersedia.", Toast.LENGTH_SHORT).show();
            }
            return true;
        }

        if ("mailto".equals(scheme) || "tel".equals(scheme) || "sms".equals(scheme)
                || "whatsapp".equals(scheme)) {
            openExternal(uri);
            return true;
        }

        return false;
    }

    private void openExternal(Uri uri) {
        try {
            Intent intent = new Intent(Intent.ACTION_VIEW, uri);
            startActivity(intent);
        } catch (ActivityNotFoundException e) {
            Toast.makeText(this, "Tidak ada aplikasi untuk membuka tautan ini.", Toast.LENGTH_SHORT).show();
        }
    }

    private void openStartPage() {
        showingErrorPage = false;
        if (!hasInternetConnection()) {
            showErrorPage(
                    "Tidak ada koneksi internet",
                    "Hadits Online membutuhkan internet. Aktifkan Wi-Fi atau data seluler lalu tekan Coba Lagi."
            );
            return;
        }
        webView.loadUrl(START_URL);
    }

    private boolean hasInternetConnection() {
        ConnectivityManager cm =
                (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        if (cm == null) return true;

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            Network network = cm.getActiveNetwork();
            if (network == null) return false;
            NetworkCapabilities caps = cm.getNetworkCapabilities(network);
            return caps != null && caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET);
        }

        @SuppressWarnings("deprecation")
        android.net.NetworkInfo info = cm.getActiveNetworkInfo();
        return info != null && info.isConnected();
    }

    private void showErrorPage(String title, String detail) {
        if (showingErrorPage) return;
        showingErrorPage = true;
        progressBar.setVisibility(View.GONE);

        String safeTitle = htmlEscape(title);
        String safeDetail = htmlEscape(detail == null ? "" : detail);

        String html = "<!doctype html><html><head>"
                + "<meta charset='utf-8'>"
                + "<meta name='viewport' content='width=device-width,initial-scale=1'>"
                + "<style>"
                + "body{margin:0;background:#eef4ff;font-family:Arial,sans-serif;color:#14213d;"
                + "display:flex;min-height:100vh;align-items:center;justify-content:center;padding:22px;box-sizing:border-box}"
                + ".card{max-width:620px;width:100%;background:#fff;border-radius:20px;padding:26px;"
                + "box-shadow:0 18px 50px rgba(15,23,42,.14);text-align:center}"
                + "h1{font-size:25px;margin:0 0 10px;color:#0f3f85}"
                + "p{font-size:16px;line-height:1.65;color:#475569}"
                + "a{display:inline-block;margin-top:12px;background:#155eef;color:#fff;text-decoration:none;"
                + "font-weight:800;padding:13px 20px;border-radius:12px}"
                + ".url{margin-top:16px;font-size:12px;color:#64748b;word-break:break-all}"
                + "</style></head><body><div class='card'>"
                + "<h1>" + safeTitle + "</h1>"
                + "<p>" + safeDetail + "</p>"
                + "<a href='haditsapp://retry'>Coba Lagi</a>"
                + "<div class='url'>" + START_URL + "</div>"
                + "</div></body></html>";

        webView.loadDataWithBaseURL(
                "https://kasmui.cloud/",
                html,
                "text/html",
                "UTF-8",
                null
        );
    }

    private String htmlEscape(String value) {
        return value.replace("&", "&amp;")
                .replace("<", "&lt;")
                .replace(">", "&gt;")
                .replace("\"", "&quot;")
                .replace("'", "&#39;");
    }

    private int dp(int value) {
        return Math.round(value * getResources().getDisplayMetrics().density);
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        webView.saveState(outState);
        super.onSaveInstanceState(outState);
    }

    @Override
    public void onBackPressed() {
        if (webView != null && webView.canGoBack() && !showingErrorPage) {
            webView.goBack();
        } else if (showingErrorPage) {
            openStartPage();
        } else {
            super.onBackPressed();
        }
    }

    @Override
    protected void onDestroy() {
        if (webView != null) {
            webView.stopLoading();
            webView.setWebChromeClient(null);
            webView.setWebViewClient(null);
            webView.destroy();
            webView = null;
        }
        super.onDestroy();
    }
}
