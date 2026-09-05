package matsantura.ampuh;

import android.content.ContentProvider;
import android.content.ContentValues;
import android.content.Context;
import android.content.UriMatcher;
import android.database.Cursor;
import android.database.MatrixCursor;
import android.net.Uri;
import android.os.ParcelFileDescriptor;
import android.provider.OpenableColumns;
import android.webkit.MimeTypeMap;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;

public class EvidenceFileProvider extends ContentProvider {
    private static final String AUTHORITY = "matsantura.ampuh.evidence";
    private static final String CAPTURE_PATH = "capture";
    private static final int CAPTURE_FILE = 1;
    private static final UriMatcher URI_MATCHER = new UriMatcher(UriMatcher.NO_MATCH);

    static {
        URI_MATCHER.addURI(AUTHORITY, CAPTURE_PATH + "/*", CAPTURE_FILE);
    }

    public static File captureDirectory(Context context) {
        File baseDir = context.getExternalCacheDir();
        if (baseDir == null) {
            baseDir = context.getCacheDir();
        }

        return new File(baseDir, "evidence_capture");
    }

    public static Uri uriForFile(Context context, File file) {
        return new Uri.Builder()
                .scheme("content")
                .authority(AUTHORITY)
                .appendPath(CAPTURE_PATH)
                .appendPath(file.getName())
                .build();
    }

    @Override
    public boolean onCreate() {
        return true;
    }

    @Override
    public String getType(Uri uri) {
        File file = fileForUri(uri);
        String extension = MimeTypeMap.getFileExtensionFromUrl(file.getName());
        String mime = extension == null ? null : MimeTypeMap.getSingleton().getMimeTypeFromExtension(extension.toLowerCase());

        if (mime != null) {
            return mime;
        }

        if (file.getName().toLowerCase().endsWith(".jpg") || file.getName().toLowerCase().endsWith(".jpeg")) {
            return "image/jpeg";
        }

        if (file.getName().toLowerCase().endsWith(".mp4")) {
            return "video/mp4";
        }

        return "application/octet-stream";
    }

    @Override
    public Cursor query(Uri uri, String[] projection, String selection, String[] selectionArgs, String sortOrder) {
        File file = fileForUri(uri);
        String[] columns = projection != null ? projection : new String[]{
                OpenableColumns.DISPLAY_NAME,
                OpenableColumns.SIZE
        };

        MatrixCursor cursor = new MatrixCursor(columns, 1);
        Object[] values = new Object[columns.length];

        for (int i = 0; i < columns.length; i++) {
            if (OpenableColumns.DISPLAY_NAME.equals(columns[i])) {
                values[i] = file.getName();
            } else if (OpenableColumns.SIZE.equals(columns[i])) {
                values[i] = file.length();
            } else {
                values[i] = null;
            }
        }

        cursor.addRow(values);
        return cursor;
    }

    @Override
    public ParcelFileDescriptor openFile(Uri uri, String mode) throws FileNotFoundException {
        File file = fileForUri(uri);
        File parent = file.getParentFile();
        if (parent != null && !parent.exists()) {
            //noinspection ResultOfMethodCallIgnored
            parent.mkdirs();
        }

        int accessMode = ParcelFileDescriptor.MODE_READ_ONLY;
        if (mode != null && (mode.contains("w") || mode.contains("+"))) {
            accessMode = ParcelFileDescriptor.MODE_READ_WRITE
                    | ParcelFileDescriptor.MODE_CREATE
                    | ParcelFileDescriptor.MODE_TRUNCATE;
        }

        return ParcelFileDescriptor.open(file, accessMode);
    }

    @Override
    public int delete(Uri uri, String selection, String[] selectionArgs) {
        File file = fileForUri(uri);
        return file.exists() && file.delete() ? 1 : 0;
    }

    @Override
    public Uri insert(Uri uri, ContentValues values) {
        return null;
    }

    @Override
    public int update(Uri uri, ContentValues values, String selection, String[] selectionArgs) {
        return 0;
    }

    private File fileForUri(Uri uri) {
        if (URI_MATCHER.match(uri) != CAPTURE_FILE) {
            throw new IllegalArgumentException("URI bukti tidak valid.");
        }

        String filename = uri.getLastPathSegment();
        if (filename == null || filename.contains("/") || filename.contains("..")) {
            throw new IllegalArgumentException("Nama file bukti tidak valid.");
        }

        try {
            File root = captureDirectory(providerContext()).getCanonicalFile();
            File file = new File(root, filename).getCanonicalFile();

            if (!file.getPath().startsWith(root.getPath() + File.separator)) {
                throw new IllegalArgumentException("Path file bukti tidak valid.");
            }

            return file;
        } catch (IOException e) {
            throw new IllegalArgumentException("File bukti tidak dapat dibaca.", e);
        }
    }

    private Context providerContext() {
        Context context = getContext();
        if (context == null) {
            throw new IllegalStateException("Context tidak tersedia.");
        }

        return context;
    }
}
