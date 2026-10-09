package br.com.petday;

import android.Manifest;
import android.annotation.SuppressLint;
import android.app.Activity;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.Color;
import android.graphics.Typeface;
import android.graphics.drawable.GradientDrawable;
import android.media.AudioAttributes;
import android.media.AudioManager;
import android.media.MediaPlayer;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.Uri;
import android.net.http.SslError;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.provider.MediaStore;
import android.util.TypedValue;
import android.view.Gravity;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.SslErrorHandler;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebResourceResponse;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;

import androidx.core.content.FileProvider;

import java.io.File;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class MainActivity extends Activity {

    public static final String EXTRA_URL = "url";
    private static final int REQ_FILE = 10;
    private static final int REQ_NOTIF = 11;
    private static final long FOREGROUND_POLL_MS = 30_000;
    /** Servidor grátis "dorme": se a 1ª carga passar disso, avisa que está acordando. */
    private static final long SLOW_HINT_MS = 6_000;

    private WebView web;
    private ProgressBar progress;
    private ValueCallback<Uri[]> fileCallback;
    private Uri cameraUri;
    private final Handler handler = new Handler(Looper.getMainLooper());
    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private boolean errorShown;
    private String failedUrl;

    // Telas próprias de carregamento/erro (nunca mostrar a página de erro padrão do WebView)
    private View loadingView;
    private TextView loadingText;
    private View errorView;
    private TextView errorTitle;
    private TextView errorText;
    private ConnectivityManager.NetworkCallback netCallback;

    private final Runnable slowHint = () ->
            loadingText.setText("Acordando o servidor…\nNa primeira abertura isso pode levar até 1 minuto.");

    /** Com o app aberto, verifica a cada 30s (o WorkManager cobre o segundo plano). */
    private final Runnable poll = new Runnable() {
        @Override public void run() {
            io.execute(() -> Notifier.check(getApplicationContext()));
            handler.postDelayed(this, FOREGROUND_POLL_MS);
        }
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        Notifier.createChannel(this);
        CheckWorker.schedule(this);

        FrameLayout root = new FrameLayout(this);
        web = new WebView(this);
        progress = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        progress.setIndeterminate(false);
        progress.setMax(100);
        root.addView(web, new FrameLayout.LayoutParams(-1, -1));
        root.addView(progress, new FrameLayout.LayoutParams(-1, 8));
        buildStatusViews(root);
        setContentView(root);

        setupWebView();
        askNotificationPermission();
        watchConnectivity();

        if (savedInstanceState != null) web.restoreState(savedInstanceState);
        else {
            playOpenSound();
            showLoading();
            load(startUrl(getIntent()));
        }
    }

    /** Jingle curto ao abrir o app (só no modo normal: respeita silencioso/vibrar). */
    private void playOpenSound() {
        AudioManager am = getSystemService(AudioManager.class);
        if (am == null || am.getRingerMode() != AudioManager.RINGER_MODE_NORMAL) return;
        MediaPlayer mp = MediaPlayer.create(this, R.raw.petday_open, new AudioAttributes.Builder()
                .setUsage(AudioAttributes.USAGE_ASSISTANCE_SONIFICATION)
                .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                .build(), am.generateAudioSessionId());
        if (mp == null) return;
        mp.setVolume(0.7f, 0.7f);
        mp.setOnCompletionListener(MediaPlayer::release);
        mp.start();
    }

    @SuppressLint("SetJavaScriptEnabled")
    private void setupWebView() {
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setUserAgentString(s.getUserAgentString() + " PetDayApp/" + BuildConfig.VERSION_NAME);

        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(web, true);

        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest req) {
                Uri uri = req.getUrl();
                Uri server = Uri.parse(Notifier.server(MainActivity.this));
                if (uri.getHost() != null && uri.getHost().equals(server.getHost())) return false;
                // Links externos (mapas, WhatsApp, etc.) abrem fora do app
                try {
                    startActivity(new Intent(Intent.ACTION_VIEW, uri));
                } catch (ActivityNotFoundException ignored) {}
                return true;
            }

            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                errorShown = false;
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                if (!errorShown) hideStatus();
                CookieManager.getInstance().flush();
                // Logou agora? Faz a primeira verificação para marcar o ponto de partida.
                io.execute(() -> Notifier.check(getApplicationContext()));
            }

            @Override
            public void onReceivedError(WebView view, WebResourceRequest req, WebResourceError err) {
                if (req.isForMainFrame()) showError(req.getUrl().toString(), false);
            }

            @Override
            public void onReceivedHttpError(WebView view, WebResourceRequest req, WebResourceResponse res) {
                // 502/503/504: servidor fora do ar ou reiniciando
                if (req.isForMainFrame() && res.getStatusCode() >= 502) showError(req.getUrl().toString(), true);
            }

            @Override
            public void onReceivedSslError(WebView view, SslErrorHandler h, SslError error) {
                h.cancel();
                showError(error.getUrl(), true);
            }
        });

        web.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int p) {
                progress.setProgress(p);
                progress.setVisibility(p < 100 ? View.VISIBLE : View.GONE);
            }

            @Override
            public boolean onShowFileChooser(WebView view, ValueCallback<Uri[]> callback, FileChooserParams params) {
                if (fileCallback != null) fileCallback.onReceiveValue(null);
                fileCallback = callback;
                openFileChooser(params);
                return true;
            }
        });
    }

    private void openFileChooser(WebChromeClient.FileChooserParams params) {
        Intent pick = params.createIntent();
        if (params.getMode() == WebChromeClient.FileChooserParams.MODE_OPEN_MULTIPLE) {
            pick.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
        }

        Intent chooser = Intent.createChooser(pick, "Escolher foto");
        cameraUri = null;
        try {
            File dir = new File(getCacheDir(), "camera");
            dir.mkdirs();
            File photo = File.createTempFile("petday_", ".jpg", dir);
            cameraUri = FileProvider.getUriForFile(this, getPackageName() + ".fileprovider", photo);
            Intent camera = new Intent(MediaStore.ACTION_IMAGE_CAPTURE)
                    .putExtra(MediaStore.EXTRA_OUTPUT, cameraUri)
                    .addFlags(Intent.FLAG_GRANT_WRITE_URI_PERMISSION);
            chooser.putExtra(Intent.EXTRA_INITIAL_INTENTS, new Intent[]{camera});
        } catch (Exception ignored) {}

        try {
            startActivityForResult(chooser, REQ_FILE);
        } catch (ActivityNotFoundException e) {
            fileCallback.onReceiveValue(null);
            fileCallback = null;
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode != REQ_FILE || fileCallback == null) return;

        Uri[] result = null;
        if (resultCode == RESULT_OK) {
            if (data != null && data.getClipData() != null) {
                int n = data.getClipData().getItemCount();
                result = new Uri[n];
                for (int i = 0; i < n; i++) result[i] = data.getClipData().getItemAt(i).getUri();
            } else if (data != null && data.getData() != null) {
                result = new Uri[]{data.getData()};
            } else if (cameraUri != null) {
                result = new Uri[]{cameraUri};
            }
        }
        fileCallback.onReceiveValue(result);
        fileCallback = null;
    }

    private void askNotificationPermission() {
        if (Build.VERSION.SDK_INT >= 33
                && checkSelfPermission(Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.POST_NOTIFICATIONS}, REQ_NOTIF);
        }
    }

    // ---- Telas de carregamento e erro ----

    private void buildStatusViews(FrameLayout root) {
        LinearLayout loading = statusColumn();
        loading.addView(pawIcon());
        loading.addView(text("PetDay", 26, true, getColor(R.color.brand)));
        ProgressBar spinner = new ProgressBar(this);
        spinner.setIndeterminateTintList(android.content.res.ColorStateList.valueOf(getColor(R.color.brand)));
        LinearLayout.LayoutParams sp = new LinearLayout.LayoutParams(dp(36), dp(36));
        sp.topMargin = dp(24);
        loading.addView(spinner, sp);
        loadingText = text("Carregando…", 15, false, Color.parseColor("#6B7280"));
        ((LinearLayout.LayoutParams) loadingText.getLayoutParams()).topMargin = dp(16);
        loading.addView(loadingText);

        LinearLayout error = statusColumn();
        error.addView(pawIcon());
        errorTitle = text("", 22, true, Color.parseColor("#1F2937"));
        error.addView(errorTitle);
        errorText = text("", 15, false, Color.parseColor("#6B7280"));
        ((LinearLayout.LayoutParams) errorText.getLayoutParams()).topMargin = dp(8);
        error.addView(errorText);
        Button retry = new Button(this);
        retry.setText("Tentar de novo");
        retry.setAllCaps(false);
        retry.setTextColor(Color.WHITE);
        retry.setTextSize(TypedValue.COMPLEX_UNIT_SP, 16);
        retry.setTypeface(Typeface.DEFAULT_BOLD);
        GradientDrawable bg = new GradientDrawable();
        bg.setColor(getColor(R.color.brand));
        bg.setCornerRadius(dp(28));
        retry.setBackground(bg);
        retry.setStateListAnimator(null);
        retry.setPadding(dp(32), 0, dp(32), 0);
        retry.setOnClickListener(v -> retry());
        LinearLayout.LayoutParams bp = new LinearLayout.LayoutParams(-2, dp(52));
        bp.topMargin = dp(28);
        error.addView(retry, bp);

        loadingView = loading;
        errorView = error;
        errorView.setVisibility(View.GONE);
        loadingView.setVisibility(View.GONE);
        root.addView(loadingView, new FrameLayout.LayoutParams(-1, -1));
        root.addView(errorView, new FrameLayout.LayoutParams(-1, -1));
    }

    private LinearLayout statusColumn() {
        LinearLayout col = new LinearLayout(this);
        col.setOrientation(LinearLayout.VERTICAL);
        col.setGravity(Gravity.CENTER);
        col.setPadding(dp(32), dp(32), dp(32), dp(32));
        col.setBackgroundColor(getColor(R.color.cream));
        col.setClickable(true); // não deixa tocar no WebView por baixo
        return col;
    }

    private ImageView pawIcon() {
        ImageView paw = new ImageView(this);
        paw.setImageResource(R.drawable.ic_stat_paw);
        paw.setColorFilter(getColor(R.color.brand));
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(dp(88), dp(88));
        lp.bottomMargin = dp(20);
        paw.setLayoutParams(lp);
        return paw;
    }

    private TextView text(String value, int sp, boolean bold, int color) {
        TextView t = new TextView(this);
        t.setText(value);
        t.setTextSize(TypedValue.COMPLEX_UNIT_SP, sp);
        t.setTextColor(color);
        t.setGravity(Gravity.CENTER);
        t.setLineSpacing(0, 1.2f);
        if (bold) t.setTypeface(Typeface.DEFAULT_BOLD);
        t.setLayoutParams(new LinearLayout.LayoutParams(-2, -2));
        return t;
    }

    private int dp(int v) {
        return Math.round(v * getResources().getDisplayMetrics().density);
    }

    private void showLoading() {
        handler.removeCallbacks(slowHint);
        loadingText.setText("Carregando…");
        errorView.setVisibility(View.GONE);
        loadingView.setVisibility(View.VISIBLE);
        handler.postDelayed(slowHint, SLOW_HINT_MS);
    }

    private void hideStatus() {
        handler.removeCallbacks(slowHint);
        loadingView.setVisibility(View.GONE);
        errorView.setVisibility(View.GONE);
    }

    private void showError(String url, boolean serverSide) {
        errorShown = true;
        failedUrl = url;
        web.stopLoading();
        handler.removeCallbacks(slowHint);
        if (!serverSide && !isOnline()) {
            errorTitle.setText("Você está sem internet");
            errorText.setText("Confira o Wi-Fi ou os dados móveis.\nAssim que a conexão voltar, a gente recarrega sozinho.");
        } else {
            errorTitle.setText("Ops! O PetDay não respondeu");
            errorText.setText("Nosso servidor está demorando mais que o normal.\nTente de novo em alguns segundos.");
        }
        loadingView.setVisibility(View.GONE);
        errorView.setVisibility(View.VISIBLE);
    }

    private void retry() {
        showLoading();
        load(failedUrl != null ? failedUrl : Notifier.server(this));
    }

    private boolean isOnline() {
        ConnectivityManager cm = getSystemService(ConnectivityManager.class);
        Network n = cm != null ? cm.getActiveNetwork() : null;
        NetworkCapabilities caps = n != null ? cm.getNetworkCapabilities(n) : null;
        return caps != null && caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET);
    }

    /** Se a internet voltar com a tela de erro aberta, tenta de novo sozinho. */
    private void watchConnectivity() {
        ConnectivityManager cm = getSystemService(ConnectivityManager.class);
        if (cm == null) return;
        netCallback = new ConnectivityManager.NetworkCallback() {
            @Override public void onAvailable(Network network) {
                handler.post(() -> { if (errorView.getVisibility() == View.VISIBLE) retry(); });
            }
        };
        cm.registerDefaultNetworkCallback(netCallback);
    }

    private String startUrl(Intent intent) {
        String url = intent != null ? intent.getStringExtra(EXTRA_URL) : null;
        return url != null ? url : Notifier.server(this);
    }

    private void load(String url) {
        web.loadUrl(url);
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        String url = intent.getStringExtra(EXTRA_URL);
        if (url != null) load(url);
    }

    @Override
    protected void onResume() {
        super.onResume();
        web.onResume();
        handler.removeCallbacks(poll);
        handler.postDelayed(poll, FOREGROUND_POLL_MS);
    }

    @Override
    protected void onPause() {
        handler.removeCallbacks(poll);
        web.onPause();
        CookieManager.getInstance().flush();
        super.onPause();
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);
        web.saveState(outState);
    }

    @Override
    public void onBackPressed() {
        if (web.canGoBack()) web.goBack();
        else super.onBackPressed();
    }

    @Override
    protected void onDestroy() {
        handler.removeCallbacksAndMessages(null);
        if (netCallback != null) {
            try { getSystemService(ConnectivityManager.class).unregisterNetworkCallback(netCallback); } catch (Exception ignored) {}
        }
        io.shutdown();
        web.destroy();
        super.onDestroy();
    }
}
