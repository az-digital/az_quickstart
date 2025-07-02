# Inspiration for improvements: https://exts.ggplot2.tidyverse.org/gallery/

# @see https://stackoverflow.com/a/4090208
list.of.packages <- c("ggplot2", "ggpmisc")
new.packages <- list.of.packages[!(list.of.packages %in% installed.packages()[,"Package"])]
if(length(new.packages)) install.packages(new.packages)

library(ggplot2)
library(ggpmisc)
df <- read.csv("statistics.csv")
df$date <- as.Date(df$date, format="%Y-%m-%d")
df$overall <- df$overall / 100
last_row <- tail(df, n=1)
ggplot(data=df, mapping=aes(x=date)) +
  scale_y_continuous(breaks = seq(0, 1, by = 0.05), labels=scales::percent) +
  coord_cartesian(ylim=c(0,1)) +
  # Relative progress.
  geom_line(linewidth=1, aes(color="RELATIVE PROGRESS", y=overall)) +
  geom_label(data=last_row, nudge_x=42, aes(y=overall), label=sprintf("%.1f%%", round(last_row$overall, 3) * 100), label.size=1, label.padding=unit(0.35, "lines")) +
  # Partially validatable.
  geom_line(linetype="dotdash", aes(color="Object property paths", y=objectPropertyPathsValidatable)) +
  geom_label(data=last_row, nudge_x=15, aes(y=objectPropertyPathsValidatable), label=sprintf("%.1f%%", round(last_row$objectPropertyPathsValidatable, 3) * 100)) +
  geom_line(linetype="dotdash", aes(color="Partially validatable used types", y=typesInUsePartiallyValidatable)) +
  geom_label(data=last_row, nudge_x=15, aes(y=typesInUsePartiallyValidatable), label=sprintf("%.1f%%", round(last_row$typesInUsePartiallyValidatable, 3) * 100)) +
  # Implicitly fully validatable.
  geom_line(aes(color="Implicitly fully validatable objects", y=objectsImplicitlyFullyValidatable)) +
  geom_line(aes(color="Implicitly fully validatable used types", y=typesInUseImplicitlyFullyValidatable)) +
  # Fully validatable.
  geom_line(aes(color="Fully validatable used types", y=typesInUseFullyValidatable)) +
  geom_line(aes(color="STANDARD Fully validatable objects", y=objectsFullyValidatable)) +
  geom_label(data=last_row, nudge_x=15, aes(y=objectsFullyValidatable), label=sprintf("%.1f%%", round(last_row$objectsFullyValidatable, 3) * 100), label.size=1, label.padding=unit(0.35, "lines")) +
  geom_line(aes(color="Fully validatable object property paths", y=objectPropertyPathsFullyValidatable)) +
  geom_line(linewidth=1, aes(color="TOTAL fully validatable types", y=typesFullyValidatable)) +
  geom_label(data=last_row, nudge_x=42, aes(y=typesFullyValidatable), label=sprintf("%.1f%%", round(last_row$typesFullyValidatable, 3) * 100), label.size=1, label.padding=unit(0.35, "lines")) +
  labs(
    title="Config schema validatability progress of Standard install profile",
    x="",
    y="Validatability (%)",
    color="Aspects"
  ) +
  # b90b2e3021bc5c25797fee48a1d23d9f3a9da804: `type: mapping` gets `ValidKeys: <infer>` by default
  geom_vline(xintercept=as.Date("2023-07-27"), linetype ="dotted", linewidth=0.5) +
  annotate("text", x=as.Date("2023-07-27"), y=0.1, label="type: mapping\n", angle=90, size=3, family="mono") +
  # b7ee3dcfcedad41a1c1c875811dd381f7f7fb059: `type: label`
  geom_vline(xintercept=as.Date("2023-08-01"), linetype="dotted", linewidth=0.5) +
  annotate("text", x=as.Date("2023-08-01"), y=0.1, label="\ntype: label", angle=90, size=3, family="mono") +
  # 5aa9704ce3a5669c55f8114f4d508dd1c3babb62: `FullyValidatableConstraint`
  geom_vline(xintercept=as.Date("2023-12-15"), linetype="longdash", linewidth=1, color="darkgreen") +
  annotate("text", x=as.Date("2023-12-15"), y=0.75, label="FullyValidatableConstraint\n", angle=90, size=4, family="mono", color="darkgreen") +
  # 7b49d8700c2075b37e73cdcf6dde3d1b1a1f5243:
  geom_vline(xintercept=as.Date("2024-01-04"), linetype="dotted", linewidth=0.5) +
  annotate("text", x=as.Date("2024-01-04"), y=0.75, label="SequenceDataDefinition::getDataType()\n", angle=90, size=3, family="mono")
ggsave("validatability.png", width=16, height=9)
