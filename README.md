# LSID Proxy

Yet another LSID resolver. Supports both the original LSID protocol, and custom work-arounds that use source-specific APIs. In other words, if the LSID has a native resolver we use that. If the native resolver no longer (or indeed, never) works, and we know the AOI to use to retrieve metadata for that LSID, we use that.

Supports either displaying original XML metadata, or a simple HTML view of that data.

## LSID specification

The official specification is hosted by the Object Management Group: [Life Sciences Identifiers version 1.0](https://www.omg.org/spec/LIS/1.0). There is a copy of this document in the `reading` folder, as well as the supporting files.

