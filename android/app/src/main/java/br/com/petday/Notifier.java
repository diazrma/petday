package br.com.petday;

import android.Manifest;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.webkit.CookieManager;

import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;
import androidx.core.content.ContextCompat;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;

/**
 * Busca no servidor as notificações novas (/notificacoes/pendentes) e as mostra no Android.
 * Usa os cookies da WebView, então funciona enquanto o usuário estiver logado no app.
 */
public final class Notifier {

    public static final String CHANNEL_ID = "atividades";
    private static final String PREFS = "petday";
    private static final String KEY_SINCE = "since";
    private static final int SUMMARY_ID = 1;
    private static final String GROUP = "petday.atividades";

    private Notifier() {}

    public static SharedPreferences prefs(Context ctx) {
        return ctx.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
    }

    /** Endereço fixo do servidor, definido no build (petdayServer). */
    public static String server(Context ctx) {
        return BuildConfig.SERVER_URL;
    }

    public static void createChannel(Context ctx) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return;
        NotificationChannel channel = new NotificationChannel(
                CHANNEL_ID, ctx.getString(R.string.channel_name), NotificationManager.IMPORTANCE_HIGH);
        channel.setDescription(ctx.getString(R.string.channel_desc));
        channel.enableVibration(true);
        channel.setShowBadge(true);
        ctx.getSystemService(NotificationManager.class).createNotificationChannel(channel);
    }

    /** Executa uma verificação (bloqueante — chamar fora da main thread). Retorna false em erro de rede. */
    public static synchronized boolean check(Context ctx) {
        String server = server(ctx);
        if (server == null) return true;

        String cookies = CookieManager.getInstance().getCookie(server);
        if (cookies == null || cookies.isEmpty()) return true; // ainda não logou

        long since = prefs(ctx).getLong(KEY_SINCE, 0);
        HttpURLConnection conn = null;
        try {
            conn = (HttpURLConnection) new URL(server + "/notificacoes/pendentes?since=" + since).openConnection();
            conn.setConnectTimeout(10000);
            conn.setReadTimeout(15000);
            conn.setInstanceFollowRedirects(false);
            conn.setRequestProperty("Accept", "application/json");
            conn.setRequestProperty("X-Requested-With", "XMLHttpRequest");
            conn.setRequestProperty("User-Agent", "PetDayApp/" + BuildConfig.VERSION_NAME);
            conn.setRequestProperty("Cookie", cookies);

            int code = conn.getResponseCode();
            if (code == 401 || code == 419) return true; // deslogado
            if (code != 200) return false;

            // Mantém a sessão viva: guarda cookies renovados pelo servidor
            for (java.util.Map.Entry<String, java.util.List<String>> h : conn.getHeaderFields().entrySet()) {
                if (h.getKey() == null || !h.getKey().equalsIgnoreCase("Set-Cookie")) continue;
                for (String c : h.getValue()) CookieManager.getInstance().setCookie(server, c);
                CookieManager.getInstance().flush();
            }

            JSONObject body = new JSONObject(read(conn.getInputStream()));
            long now = body.getLong("now");
            JSONArray items = body.getJSONArray("notifications");

            // Primeira verificação: só marca o ponto de partida, sem despejar notificações antigas.
            if (since > 0) {
                for (int i = items.length() - 1; i >= 0; i--) show(ctx, server, items.getJSONObject(i));
                if (items.length() > 1) showSummary(ctx, body.optInt("unread", items.length()));
            }
            prefs(ctx).edit().putLong(KEY_SINCE, now).apply();
            return true;
        } catch (Exception e) {
            return false;
        } finally {
            if (conn != null) conn.disconnect();
        }
    }

    private static void show(Context ctx, String server, JSONObject n) {
        if (!canNotify(ctx)) return;
        String text = n.optString("icon", "🐾") + " " + n.optString("text", "");

        NotificationCompat.Builder b = new NotificationCompat.Builder(ctx, CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_stat_paw)
                .setColor(ContextCompat.getColor(ctx, R.color.brand))
                .setContentTitle("PetDay")
                .setContentText(text)
                .setStyle(new NotificationCompat.BigTextStyle().bigText(text))
                .setWhen(n.optLong("created_at", System.currentTimeMillis() / 1000) * 1000)
                .setShowWhen(true)
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setCategory(NotificationCompat.CATEGORY_SOCIAL)
                .setDefaults(NotificationCompat.DEFAULT_ALL)
                .setGroup(GROUP)
                .setAutoCancel(true)
                .setContentIntent(open(ctx, localUrl(server, n.optString("url", null)), n.optString("id").hashCode()));

        NotificationManagerCompat.from(ctx).notify(n.optString("id").hashCode(), b.build());
    }

    private static void showSummary(Context ctx, int unread) {
        if (!canNotify(ctx)) return;
        String server = server(ctx);
        NotificationCompat.Builder b = new NotificationCompat.Builder(ctx, CHANNEL_ID)
                .setSmallIcon(R.drawable.ic_stat_paw)
                .setColor(ContextCompat.getColor(ctx, R.color.brand))
                .setContentTitle("PetDay")
                .setContentText(unread + " notificações novas")
                .setNumber(unread)
                .setGroup(GROUP)
                .setGroupSummary(true)
                .setAutoCancel(true)
                .setContentIntent(open(ctx, server + "/notificacoes", SUMMARY_ID));
        NotificationManagerCompat.from(ctx).notify(SUMMARY_ID, b.build());
    }

    /** As URLs vêm com o host que o servidor conhece; reaproveita só o caminho no servidor do app. */
    static String localUrl(String server, String url) {
        if (url == null || url.isEmpty() || "null".equals(url)) return server + "/notificacoes";
        Uri u = Uri.parse(url);
        String path = u.getEncodedPath() == null ? "/" : u.getEncodedPath();
        String query = u.getEncodedQuery() == null ? "" : "?" + u.getEncodedQuery();
        return server + path + query;
    }

    private static PendingIntent open(Context ctx, String url, int requestCode) {
        Intent i = new Intent(ctx, MainActivity.class)
                .setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_SINGLE_TOP)
                .putExtra(MainActivity.EXTRA_URL, url);
        return PendingIntent.getActivity(ctx, requestCode, i,
                PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);
    }

    private static boolean canNotify(Context ctx) {
        return Build.VERSION.SDK_INT < 33
                || ContextCompat.checkSelfPermission(ctx, Manifest.permission.POST_NOTIFICATIONS) == PackageManager.PERMISSION_GRANTED;
    }

    private static String read(InputStream in) throws java.io.IOException {
        ByteArrayOutputStream out = new ByteArrayOutputStream();
        byte[] buf = new byte[4096];
        int r;
        while ((r = in.read(buf)) != -1) out.write(buf, 0, r);
        return out.toString("UTF-8");
    }
}
