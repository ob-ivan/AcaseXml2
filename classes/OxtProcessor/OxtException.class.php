<?php

class OxtException extends Exception {}

class OxtExceptionParse extends OxtException 
{
    const CODE_BAD_TOKEN                    = __LINE__;
    const CODE_BAD_TOP_LEVEL_TERM           = __LINE__;
    const CODE_DUPLICATE_NAME               = __LINE__;
    const CODE_NOT_IMPLEMENTED              = __LINE__;
    const CODE_UNKNOWN_CONTROL              = __LINE__;
    const CODE_MISSING_EXPRESSION           = __LINE__;
    const CODE_MISSING_CLOSING_PARENTHESIS  = __LINE__;
    const CODE_MISMATCHING_PARENTHESES      = __LINE__;
    const CODE_ERROR_IN_EXPRESSION          = __LINE__;
    const CODE_WRONG_AXIS_SPECIFIER         = __LINE__;
    const CODE_UNKNOWN_NODE_TYPE_TEST       = __LINE__;
    const CODE_BAD_SLICE_INDEX              = __LINE__;
}

class OxtExceptionCompile extends OxtException
{
    const CODE_DUPLICATE_MAIN               = __LINE__;
    const CODE_DUPLICATE_TEMPLATE_NAME      = __LINE__;
    const CODE_ABSENT_MAIN                  = __LINE__;
    const CODE_NOT_IMPLEMENTED              = __LINE__;
    const CODE_UNKNOWN_NODE_TYPE            = __LINE__;
    const CODE_INVALID_EXPRESSION_TYPE      = __LINE__;
    const CODE_ILLEGAL_TYPE_CONVERSION      = __LINE__;
    const CODE_CALL_UNDEFINED_TEMPLATE      = __LINE__;
    const CODE_FAILED_EVALUATE_XPATH        = __LINE__;
    const CODE_ILLEGAL_NODE_TYPE            = __LINE__;
    const CODE_WRONG_ARGUMENT_COUNT         = __LINE__;
    const CODE_UNDEFINED_VARIABLE           = __LINE__;
    const CODE_CALL_UNDEFINED_FUNCTION      = __LINE__;
    const CODE_DIVISION_BY_ZERO             = __LINE__;
    const CODE_ILLEGAL_ARGUMENT_VALUE       = __LINE__;
}

class OxtExceptionInternal extends OxtException 
{
    const CODE_PUSH_TO_FINALIZED            = __LINE__;
    const CODE_UNEXPECTED_RETURN_TYPE       = __LINE__;
    const CODE_UNDEFINED_OFFSET             = __LINE__;
    const CODE_UNREACHABLE_CODE             = __LINE__;
}

