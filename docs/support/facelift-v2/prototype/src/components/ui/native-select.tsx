import type { ComponentProps } from "react";
import { ChevronDown } from "lucide-react";
import { cn } from "./utils";

export function NativeSelect({
  className,
  containerClassName,
  ...props
}: ComponentProps<"select"> & { containerClassName?: string }) {
  return (
    <span
      data-slot="native-select-wrapper"
      className={cn(
        "relative inline-block w-fit max-w-full has-[select:disabled]:opacity-50",
        containerClassName,
      )}
    >
      <select
        data-slot="native-select"
        className={cn(
          "min-h-11 w-full min-w-0 cursor-pointer appearance-none rounded-md border border-input bg-background px-3 py-2 pr-9 text-sm",
          "text-foreground focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-ring",
          "disabled:pointer-events-none disabled:cursor-not-allowed aria-invalid:border-destructive",
          className,
        )}
        {...props}
      />
      <ChevronDown
        className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
        aria-hidden="true"
      />
    </span>
  );
}

export function NativeSelectOption(props: ComponentProps<"option">) {
  return <option data-slot="native-select-option" {...props} />;
}

export function NativeSelectOptGroup(props: ComponentProps<"optgroup">) {
  return <optgroup data-slot="native-select-optgroup" {...props} />;
}
