import logging
import threading

_local = threading.local()


def set_request_id(value: str | None) -> None:
    _local.request_id = value


def get_request_id() -> str:
    return getattr(_local, "request_id", None) or "-"


class RequestIDFilter(logging.Filter):
    def filter(self, record: logging.LogRecord) -> bool:
        record.request_id = get_request_id()
        return True
