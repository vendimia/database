<?php
namespace Vendimia\Database;

/**
 * Vendimia database field types
 */
enum FieldType
{
    case AutoIncrement;

    // Integers
    case Boolean;
    case Byte;
    case SmallInt;
    case Integer;
    case BigInt;

    // Decimals
    case Float;
    case Double;
    case Decimal;

    // Strings
    case Char;
    case FixChar;
    case Text;
    case MediumText;
    case LongText;
    case Blob;

    // Date/Time
    case Date;
    case Time;
    case DateTime;

    // ForeignKey
    case ForeignKey;

    // JSON
    case JSON;

    // Enum
    case Enum;
}
