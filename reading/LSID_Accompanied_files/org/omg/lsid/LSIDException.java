package org.omg.lsid;

public class LSIDException extends Exception {

    /** Error codes dealing with LSID parameter */
    public static final int MALFORMED_LSID = 200;
    public static final int UNKNOWN_LSID = 201;
    public static final int CANNOT_ASSIGN_LSID = 202;

    /** Error codes dealing with data */
    public static final int NO_DATA_AVAILABLE = 300;
    public static final int INVALID_RANGE = 301;

    /** Error codes dealing with metadata */
    public static final int NO_METADATA_AVAILABLE = 400;
    public static final int NO_METADATA_AVAILABLE_FOR_FORMAT = 401;
    public static final int UNKNOWN_SELECTOR_FORMAT = 402;

    /** General error codes */
    public static final int INTERNAL_PROCESSING ERROR = 500;
    public static final int METHOD_NOT_IMPLEMENTED = 501;

    protected int errorCode;

    public LSIDException() { super(); }
    public LSIDException (int errorCode,
			  String reason) {
	super (reason);
	this.errorCode = errorCode;
    }

    public int getErrorCode() {
	return errorCode;
    }

}
