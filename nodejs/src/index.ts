import "reflect-metadata";
import { AppDataSource } from "./data-source";
import { runDemo } from "./demo";

async function main() {
  await AppDataSource.initialize();
  console.log("Database connected.\n");
  await runDemo(AppDataSource);
  await AppDataSource.destroy();
}

main().catch(console.error);
