# LSID Proxy

Yet another LSID resolver. Supports both the original LSID protocol, and custom work-arounds that use source-specific APIs. In other words, if the LSID has a native resolver we use that. If the native resolver no longer (or indeed, never) works, and we know the AOI to use to retrieve metadata for that LSID, we use that.

Supports either displaying original XML metadata, or a simple HTML view of that data.
